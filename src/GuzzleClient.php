<?php

namespace RCConsulting\FileMakerApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use RCConsulting\FileMakerApi\Exception\Exception;

/**
 * Class GuzzleClient
 * 
 * Guzzle-based HTTP client implementation for the FileMaker Data API.
 * This implementation uses the Guzzle HTTP client library instead of cURL directly.
 *
 * @package RCConsulting\FileMakerApi
 */
final class GuzzleClient implements HttpClientInterface
{
    private bool $sslVerify;
    private string $baseUrl;
    private bool $forceLegacyHTTP;
    private Client $client;

    /**
     * GuzzleClient constructor
     *
     * @param string $apiUrl The base URL for the FileMaker Data API
     * @param bool   $sslVerify Whether to verify SSL certificates
     * @param bool   $forceLegacyHTTP Whether to force HTTP/1.1 instead of HTTP/2
     */
    public function __construct(string $apiUrl, bool $sslVerify, bool $forceLegacyHTTP)
    {
        $this->sslVerify = $sslVerify;
        $this->baseUrl = $apiUrl;
        $this->forceLegacyHTTP = $forceLegacyHTTP;

        // Configure Guzzle client options
        $clientOptions = [
            'base_uri' => $this->baseUrl,
            'verify' => $this->sslVerify,
            'allow_redirects' => true,
        ];

        // Force HTTP/1.1 if requested
        if ($this->forceLegacyHTTP) {
            $clientOptions['version'] = '1.1';
        }

        $this->client = new Client($clientOptions);
    }

    /**
     * Execute an HTTP request using Guzzle
     *
     * @param string $method HTTP method (GET, POST, DELETE, etc.)
     * @param string $url The endpoint URL (relative to base URL)
     * @param array  $options Request options including:
     *                       - headers: array of HTTP headers
     *                       - json: array of data to be JSON encoded
     *                       - file: array with file upload information (path, name)
     *                       - query_params: array of query parameters
     *
     * @return Response The response object containing headers, body, and status
     * @throws Exception When the request fails or returns an error
     */
    public function request(string $method, string $url, array $options): Response
    {
        try {
            $guzzleOptions = [];

            // Set headers
            if (isset($options['headers'])) {
                $guzzleOptions[RequestOptions::HEADERS] = $options['headers'];
            }

            // Handle JSON payload
            if (isset($options['json']) && !empty($options['json']) && $method !== 'GET') {
                $guzzleOptions[RequestOptions::JSON] = $options['json'];
            }

            // Handle file upload
            if (isset($options['file']) && !empty($options['file']) && $method === 'POST') {
                $guzzleOptions[RequestOptions::MULTIPART] = [
                    [
                        'name' => 'upload',
                        'contents' => fopen($options['file']['path'], 'r'),
                        'filename' => $options['file']['name'],
                    ]
                ];
            }

            // Handle query parameters
            if (isset($options['query_params']) && !empty($options['query_params'])) {
                $guzzleOptions[RequestOptions::QUERY] = $options['query_params'];
            }

            // Make the request
            $guzzleResponse = $this->client->request($method, $url, $guzzleOptions);

            // Convert Guzzle response to our Response format
            $headers = [];
            foreach ($guzzleResponse->getHeaders() as $name => $values) {
                $headers[$name] = implode(', ', $values);
            }

            // Add status line for compatibility with existing Response parsing
            $headers['Status'] = sprintf(
                'HTTP/%s %d %s',
                $guzzleResponse->getProtocolVersion(),
                $guzzleResponse->getStatusCode(),
                $guzzleResponse->getReasonPhrase()
            );

            $body = (string) $guzzleResponse->getBody();
            $response = Response::parse($this->formatHeaders($headers), $body);

            $this->validateResponse($response);

            return $response;

        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Format headers array into string format expected by Response::parse()
     *
     * @param array $headers
     * @return string
     */
    private function formatHeaders(array $headers): string
    {
        $headerString = '';
        foreach ($headers as $name => $value) {
            $headerString .= $name . ': ' . $value . "\n";
        }
        return $headerString;
    }

    /**
     * Validate the response and throw exceptions for error conditions
     *
     * @param Response $response
     * @throws Exception
     */
    private function validateResponse(Response $response): void
    {
        if ($response->getHttpCode() >= 400 && $response->getHttpCode() < 600 || $response->getHttpCode() === 100) {
            if (isset($response->getBody()['messages'][0]['message'])) {
                $eMessage = is_array($response->getBody()['messages'][0]['message']) 
                    ? implode(' - ', $response->getBody()['messages'][0]['message']) 
                    : $response->getBody()['messages'][0]['message'];
                $eCode = isset($response->getBody()['messages'][0]['code']) 
                    ? $response->getBody()['messages'][0]['code'] 
                    : $response->getHttpCode();

                throw new Exception($eMessage, $eCode);
            }

            // A status code 100 with no message is OK
            if ($response->getHttpCode() !== 100) {
                $message = is_array($response->getBody()) || is_object($response->getBody()) 
                    ? json_encode($response->getBody()) 
                    : $response->getBody();

                if (empty($message)) {
                    $message = $response->getHeader('Status');
                }

                throw new Exception($message, $response->getHttpCode());
            }
        }
    }
}

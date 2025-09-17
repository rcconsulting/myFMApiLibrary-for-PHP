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
        // Ensure base URL ends with slash for proper Guzzle URL resolution
        $baseUri = rtrim($apiUrl, '/') . '/';

        $this->sslVerify = $sslVerify;
        $this->baseUrl = $baseUri;
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
     *                       - JSON: array of data to be JSON encoded
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

            // Remove leading slash from URL since we're using base_uri
            $url = ltrim($url, '/');

            // Handle JSON payload - only set JSON option if there's actual data
            if (!empty($options['json']) && $method !== 'GET') {
                $guzzleOptions[RequestOptions::JSON] = $options['json'];
            } elseif (isset($options['json']) && $method !== 'GET') {
                // For empty JSON arrays, send empty JSON object with proper headers
                $guzzleOptions[RequestOptions::BODY] = '{}';
                $guzzleOptions[RequestOptions::HEADERS] = array_merge(
                    $options['headers'] ?? [],
                    ['Content-Type' => 'application/json']
                );
            } else {
                // Set headers for non-JSON requests
                if (isset($options['headers'])) {
                    $guzzleOptions[RequestOptions::HEADERS] = $options['headers'];
                }
            }

            // Handle file upload
            if (!empty($options['file']) && $method === 'POST') {
                // Remove JSON options for file uploads
                unset($guzzleOptions[RequestOptions::JSON]);
                unset($guzzleOptions[RequestOptions::BODY]);

                $guzzleOptions[RequestOptions::MULTIPART] = [
                    [
                        'name' => 'upload',
                        'contents' => fopen($options['file']['path'], 'r'),
                        'filename' => $options['file']['name'],
                    ]
                ];

                // Set headers for file upload
                if (isset($options['headers'])) {
                    $guzzleOptions[RequestOptions::HEADERS] = $options['headers'];
                }
            }

            // Handle query parameters
            if (!empty($options['query_params'])) {
                $guzzleOptions[RequestOptions::QUERY] = $options['query_params'];
            }

            // Make the request
            $guzzleResponse = $this->client->request($method, $url, $guzzleOptions);

            // Convert Guzzle response to our Response format
            $headers = array_map(function ($values) {
                return implode(', ', $values);
            }, $guzzleResponse->getHeaders());

            // Add a status line for compatibility with existing Response parsing
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
     * Format headers array into the string format expected by Response::parse()
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
                $eCode = $response->getBody()['messages'][0]['code'] ?? $response->getHttpCode();

                throw new Exception($eMessage, $eCode);
            }

            // Status code 100 with no message is OK
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

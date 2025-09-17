<?php

namespace RCConsulting\FileMakerApi;

use CURLFile;
use RCConsulting\FileMakerApi\Exception\Exception;

/**
 * Class CurlClient
 *
 * @package RCConsulting\DataApi
 */
final class CurlClient implements HttpClientInterface
{
    private bool $sslVerify;
    private ?string $baseUrl;
    private bool $forceLegacyHTTP;

    /**
     * CurlClient constructor
     *
     * @param string $apiUrl
     * @param bool   $sslVerify
     * @param bool   $forceLegacyHTTP
     */
    public function __construct(string $apiUrl, bool $sslVerify, bool $forceLegacyHTTP)
    {
        $this->sslVerify = $sslVerify;
        $this->baseUrl = $apiUrl;
        $this->forceLegacyHTTP = $forceLegacyHTTP;
    }

    /**
     * Execute a cURL request
     *
     * @param string $method
     * @param string $url
     * @param array  $options
     *
     * @return Response
     * @throws Exception
     */
    public function request(string $method, string $url, array $options): Response
    {
        $ch = curl_init();
        if ($ch === False) {
            throw new Exception('Failed to initialize curl');
        }

        $headers = [];
        $completeUrl = $this->baseUrl . $url;

        if ($this->sslVerify) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($this->forceLegacyHTTP) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, True);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POST, $method === 'POST');

        $contentLength = 0;
        if (isset($options['json']) && !empty($options['json']) && $method !== 'GET') {
            $body = json_encode($options['json']);

            if ($body === False) {
                throw new Exception("Failed to json encode parameters");
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

            $contentLength = mb_strlen($body);
        }

        if (isset($options['file']) && !empty($options['file']) && $method === 'POST') {
            $cURLFile = new CURLFile($options['file']['path'], mime_content_type($options['file']['path']), $options['file']['name']);

            curl_setopt($ch, CURLOPT_POSTFIELDS, ['upload' => $cURLFile]);

            $contentLength = False;
        }

        //-- Set headers
        if (!isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = 'application/json';
        }

        if (!isset($options['headers']['Content-Length']) && $contentLength !== False) {
            $options['headers']['Content-Length'] = $contentLength;
        }

        foreach ($options['headers'] as $headerKey => $headerValue) {
            $headers[] = $headerKey . ':' . $headerValue;
        }
        //--

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, True);

        if (isset($options['query_params']) && !empty($options['query_params'])) {
            $query_params = http_build_query($options['query_params']);
            $completeUrl .= (!empty($query_params) ? '?' . $query_params : '');
        }

        curl_setopt($ch, CURLOPT_URL, $completeUrl);

        $result = curl_exec($ch);

        if ($result === False) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }

        $responseHeaders = substr($result, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $body = substr($result, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $response = Response::parse($responseHeaders, $body);

        curl_close($ch);

        $this->validateResponse($response);

        return $response;
    }

    /**
     * @param Response $response
     *
     * @throws Exception
     */
    private function validateResponse(Response $response): void
    {
        if ($response->getHttpCode() === 100 || ($response->getHttpCode() >= 400 && $response->getHttpCode() < 600)) {
            if (isset($response->getBody()['messages'][0]['message'])) {
                $eMessage = is_array($response->getBody()['messages'][0]['message']) ? implode(' - ', $response->getBody()['messages'][0]['message']) : $response->getBody()['messages'][0]['message'];
                $eCode = $response->getBody()['messages'][0]['code'] ?? $response->getHttpCode();

                throw new Exception($eMessage, $eCode);
            }

            // A status code 100 with no message is OK
            if ($response->getHttpCode() !== 100) {
                $message = is_array($response->getBody() || is_object($response->getBody()) ?
                    json_encode($response->getBody()) : $response->getBody());

                if (empty($message)) {
                    $message = $response->getHeader('Status');
                }

                throw new Exception($message, $response->getHttpCode());
            }
        }
    }
}

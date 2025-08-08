<?php

namespace RCConsulting\FileMakerApi;

use RCConsulting\FileMakerApi\Exception\Exception;

/**
 * Interface HttpClientInterface
 * 
 * Defines the contract for HTTP client implementations used by the FileMaker Data API.
 * This interface abstracts the HTTP transport layer to allow for different implementations
 * such as cURL-based or Guzzle-based clients.
 *
 * @package RCConsulting\FileMakerApi
 */
interface HttpClientInterface
{
    /**
     * HttpClientInterface constructor
     *
     * @param string $apiUrl The base URL for the FileMaker Data API
     * @param bool   $sslVerify Whether to verify SSL certificates
     * @param bool   $forceLegacyHTTP Whether to force HTTP/1.1 instead of HTTP/2
     */
    public function __construct(string $apiUrl, bool $sslVerify, bool $forceLegacyHTTP);

    /**
     * Execute an HTTP request
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
    public function request(string $method, string $url, array $options): Response;
}

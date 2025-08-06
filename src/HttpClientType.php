<?php

namespace RCConsulting\FileMakerApi;

use ValueError;

/**
 * Enumeration for HTTP client types
 *
 * Defines the available HTTP client implementations for the FileMaker Data API.
 * This enum provides type safety and prevents invalid client type values.
 *
 * @package RCConsulting\FileMakerApi
 */
enum HttpClientType: string
{
    case CURL = 'curl';
    case GUZZLE = 'guzzle';

    /**
     * Get the string value of the enum case
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Create enum from string value (case-insensitive)
     *
     * @param string $value
     * @return HttpClientType
     * @throws ValueError When an invalid value is provided
     */
    public static function fromString(string $value): HttpClientType
    {
        return match (strtolower($value)) {
            'curl' => self::CURL,
            'guzzle' => self::GUZZLE,
            default => throw new ValueError("Invalid HTTP client type: '$value'. Supported types are 'curl' and 'guzzle'.")
        };
    }
}

<?php

namespace RCConsulting\FileMakerApi;

enum HttpClientType: string
{
    case CURL = 'curl';
    case GUZZLE = 'guzzle';

    public function getValue(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'curl' => self::CURL,
            'guzzle' => self::GUZZLE,
            default => throw new \ValueError("Invalid HTTP client type: '$value'. Supported types are 'curl' and 'guzzle'.")
        };
    }
}

<?php

namespace RCConsulting\FileMakerApi;

enum DapiVersion: string
{
    case V1 = 'v1';
    case V2 = 'v2';
    case VLATEST = 'vLatest';

    public function getValue(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'v1' => self::V1,
            'v2' => self::V2,
            'vlatest' => self::VLATEST,
            default => throw new \ValueError("Invalid API version: '$value'. Supported versions are 'v1', 'v2', and 'vLatest'.")
        };
    }
}

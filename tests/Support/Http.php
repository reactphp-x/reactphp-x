<?php

declare(strict_types=1);

namespace Tests\Support;

use Psr\Http\Message\ResponseInterface;
use React\Http\Message\ServerRequest;

final class Http
{
    public static function jsonRequest(string $method, string $url, array $body = []): ServerRequest
    {
        return new ServerRequest(
            $method,
            $url,
            ['Content-Type' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    public static function decodeJson(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }
}

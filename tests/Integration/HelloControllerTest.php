<?php

declare(strict_types=1);

use App\Exceptions\BusinessException;
use App\Http\Controllers\HelloController;
use React\Http\Message\ServerRequest;
use Tests\Support\Http;
use Tests\Support\UserFactory;

test('index returns hello message', function (): void {
    $response = (new HelloController())->index(new ServerRequest('GET', '/'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getHeaderLine('Content-Type'))->toBe('application/json');

    $payload = Http::decodeJson($response);
    expect($payload['message'])->toBe('Hello, API!')
        ->and($payload['user'])->toBeNull();
});

test('index includes user when present', function (): void {
    UserFactory::create('Alice', 'alice@example.com');

    $response = (new HelloController())->index(new ServerRequest('GET', '/'));
    $payload = Http::decodeJson($response);

    expect($payload['user'])->toBeArray()
        ->and($payload['user']['name'])->toBe('Alice')
        ->and($payload['user']['email'])->toBe('alice@example.com');
});

test('testException throws business exception', function (): void {
    (new HelloController())->testException();
})->throws(BusinessException::class, 'Test exception');

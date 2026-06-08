<?php

declare(strict_types=1);

use App\Http\Controllers\UserController;
use App\Models\PersonalAccessToken;
use React\Http\Message\ServerRequest;
use Tests\Support\Http;
use Tests\Support\UserFactory;

test('issue returns 422 for invalid json body', function (): void {
    $request = new ServerRequest('POST', '/api/tokens', [], 'not-json');

    $response = (new UserController())->issue($request);
    $payload = Http::decodeJson($response);

    expect($response->getStatusCode())->toBe(200)
        ->and($payload['code'])->toBe(422)
        ->and($payload['msg'])->toBe('Invalid JSON body');
});

test('issue returns 422 when user_id is missing', function (): void {
    $request = Http::jsonRequest('POST', '/api/tokens', []);

    $response = (new UserController())->issue($request);
    $payload = Http::decodeJson($response);

    expect($payload['code'])->toBe(422)
        ->and($payload['msg'])->toBe('user_id is required');
});

test('issue returns 404 when user does not exist', function (): void {
    $request = Http::jsonRequest('POST', '/api/tokens', ['user_id' => 999]);

    $response = (new UserController())->issue($request);
    $payload = Http::decodeJson($response);

    expect($payload['code'])->toBe(404)
        ->and($payload['msg'])->toBe('User not found');
});

test('issue returns 422 for invalid expires_at', function (): void {
    $user = UserFactory::create();
    $request = Http::jsonRequest('POST', '/api/tokens', [
        'user_id' => $user->id,
        'expires_at' => 'not-a-date',
    ]);

    $response = (new UserController())->issue($request);
    $payload = Http::decodeJson($response);

    expect($payload['code'])->toBe(422)
        ->and($payload['msg'])->toBe('expires_at must be a valid date-time');
});

test('issue creates token for existing user', function (): void {
    $user = UserFactory::create('Bob', 'bob@example.com');
    $request = Http::jsonRequest('POST', '/api/tokens', [
        'user_id' => $user->id,
        'name' => 'mobile-app',
        'abilities' => ['read'],
    ]);

    $response = (new UserController())->issue($request);
    $payload = Http::decodeJson($response);

    expect($payload['code'])->toBe(0)
        ->and($payload['data']['name'])->toBe('mobile-app')
        ->and($payload['data']['token'])->toBeString()
        ->and($payload['data']['token_id'])->toBeGreaterThan(0);
});

test('me returns 401 when user attribute is missing', function (): void {
    $request = new ServerRequest('GET', '/api/user');

    $response = (new UserController())->me($request);
    $payload = Http::decodeJson($response);

    expect($response->getStatusCode())->toBe(200)
        ->and($payload['code'])->toBe(401)
        ->and($payload['msg'])->toBe('Unauthorized');
});

test('me returns current user snapshot', function (): void {
    $user = UserFactory::create('Carol', 'carol@example.com');

    $accessToken = new PersonalAccessToken();
    $accessToken->name = 'test-token';
    $user->withAccessToken($accessToken);

    $request = (new ServerRequest('GET', '/api/user'))
        ->withAttribute('user', $user);

    $response = (new UserController())->me($request);
    $payload = Http::decodeJson($response);

    expect($payload['code'])->toBe(0)
        ->and($payload['data']['id'])->toBe($user->id)
        ->and($payload['data']['name'])->toBe('Carol')
        ->and($payload['data']['status'])->toBe(1)
        ->and($payload['data']['access_token'])->toBeArray()
        ->and($payload['data']['access_token']['name'])->toBe('test-token');
});

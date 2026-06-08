<?php

declare(strict_types=1);

use App\Http\Support\BearerTokenAuthenticator;
use React\Http\Message\ServerRequest;

test('getRawToken from authorization header', function (): void {
    $request = (new ServerRequest('GET', '/'))
        ->withHeader('Authorization', 'Bearer abc123');

    expect(BearerTokenAuthenticator::getRawTokenFromRequest($request))->toBe('abc123');
});

test('getRawToken from query parameter', function (): void {
    $request = new ServerRequest('GET', '/?token=query-token');

    expect(BearerTokenAuthenticator::getRawTokenFromRequest($request))->toBe('query-token');
});

test('getRawToken returns null when missing', function (): void {
    $request = new ServerRequest('GET', '/');

    expect(BearerTokenAuthenticator::getRawTokenFromRequest($request))->toBeNull();
});

test('isValidBearerTokenFormat', function (): void {
    expect(BearerTokenAuthenticator::isValidBearerTokenFormat('plain-token'))->toBeTrue()
        ->and(BearerTokenAuthenticator::isValidBearerTokenFormat('42|secret'))->toBeTrue()
        ->and(BearerTokenAuthenticator::isValidBearerTokenFormat(''))->toBeFalse()
        ->and(BearerTokenAuthenticator::isValidBearerTokenFormat('abc|'))->toBeFalse()
        ->and(BearerTokenAuthenticator::isValidBearerTokenFormat('|secret'))->toBeFalse()
        ->and(BearerTokenAuthenticator::isValidBearerTokenFormat('id|'))->toBeFalse();
});

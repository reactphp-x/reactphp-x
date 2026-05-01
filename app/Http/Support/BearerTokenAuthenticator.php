<?php

namespace App\Http\Support;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads bearer tokens and resolves {@see User} + {@see PersonalAccessToken} via Cycle ORM.
 */
final class BearerTokenAuthenticator
{
    /**
     * Raw bearer string from query `token` or `Authorization: Bearer`, or null if absent.
     */
    public static function getRawTokenFromRequest(ServerRequestInterface $request): ?string
    {
        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'] ?? null;

        if (empty($token)) {
            $auth = $request->getHeaderLine('Authorization');
            if (stripos($auth, 'Bearer ') === 0) {
                $token = trim(substr($auth, 7));
            }
        }

        if (empty($token)) {
            return null;
        }

        return self::isValidBearerTokenFormat((string) $token) ? (string) $token : null;
    }

    /**
     * Mirrors Sanctum Guard::isValidBearerToken (pipe form requires numeric id).
     */
    public static function isValidBearerTokenFormat(string $token): bool
    {
        if (str_contains($token, '|')) {
            [$id, $value] = explode('|', $token, 2);

            return ctype_digit((string) $id) && $value !== '';
        }

        return $token !== '';
    }

    /**
     * @return array{user: User, token: PersonalAccessToken}|null
     */
    public static function authenticateRaw(string $rawToken): ?array
    {
        $accessToken = PersonalAccessToken::findToken($rawToken);
        if ($accessToken === null) {
            return null;
        }

        $user = $accessToken->tokenable;
        if (! $user instanceof User) {
            return null;
        }

        return ['user' => $user, 'token' => $accessToken];
    }
}

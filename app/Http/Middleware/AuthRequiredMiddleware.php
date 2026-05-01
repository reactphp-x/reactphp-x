<?php

namespace App\Http\Middleware;

use App\Http\Support\BearerTokenAuthenticator;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

class AuthRequiredMiddleware
{
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        $raw = BearerTokenAuthenticator::getRawTokenFromRequest($request);

        if ($raw === null) {
            return $this->unauthorizedResponse();
        }

        $auth = BearerTokenAuthenticator::authenticateRaw($raw);
        if ($auth === null) {
            return $this->unauthorizedResponse();
        }

        $request = $request
            ->withAttribute('user', $auth['user'])
            ->withAttribute('access_token', $auth['token']);

        return $next($request);
    }

    private function unauthorizedResponse()
    {
        return Response::json([
            'code' => 401,
            'msg' => 'Unauthorized',
        ]);
    }
}

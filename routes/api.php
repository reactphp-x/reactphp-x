<?php

use App\Http\Controllers\HelloController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthRequiredMiddleware;
use React\Http\Message\Response;
use ReactphpX\Route\Route;

/** @var Route $route */

$route->group('', function (Route $route) {
    $hello = app(HelloController::class);

    $route->get('/', [$hello, 'index']);
    $route->get('/test-exception', [$hello, 'testException']);

    $route->group('/api', function (Route $route) {
        $userController = app(UserController::class);

        // test token issue
        // $route->post('/tokens', [$userController, 'issue']);

        $route->middleware(AuthRequiredMiddleware::class)->group(function (Route $route) use ($userController) {
            $route->get('/user', [$userController, 'me']);
        });

        $route->get('/docs.openapi', static function () {
            $generator = new \OpenApi\Generator();
            $openapi = $generator->generate(
                \Symfony\Component\Finder\Finder::create()->in(base_path('app'))->files()->name('*.php')
            );

            return Response::plaintext($openapi->toYaml());
        });
    });
});

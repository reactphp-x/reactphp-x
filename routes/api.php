<?php

use ReactphpX\Route\Route;
use React\Http\Message\Response;

/** @var Route $route */
$route->group('', function (Route $route) {
    $controller = app(\App\Http\Controllers\HelloController::class);
    $route->get('/', [$controller, 'index']);
    $route->get('/test-exception', [$controller, 'testException']);
});


$route->get('/api/docs.openapi', function () {
    $generator = new \OpenApi\Generator();
    $openapi = $generator->generate(\Symfony\Component\Finder\Finder::create()->in(base_path('app'))->files()->name('*.php'));
    return Response::plaintext($openapi->toYaml());
});
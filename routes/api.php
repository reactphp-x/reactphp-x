<?php

use ReactphpX\Route\Route;

/** @var Route $route */
$route->group('', function (Route $route) {
    $controller = app(\App\Http\Controllers\HelloController::class);
    $route->get('/', [$controller, 'index']);
    $route->get('/test-exception', [$controller, 'testException']);
});

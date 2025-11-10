<?php

use ReactphpX\Route\Route;

/** @var Route $route */
$route->group('', function (Route $route) {
    $route->get('/', \App\Http\Controllers\HelloController::class . '@index');
    $route->get('/test-exception', \App\Http\Controllers\HelloController::class . '@testException');
});

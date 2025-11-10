<?php

use ReactphpX\Route\Route;

/** @var Route $route */
$route->group('/api', function (Route $route) {
    $route->get('/hello', \App\Http\Controllers\HelloController::class . '@index');
    $route->get('/test-exception', \App\Http\Controllers\HelloController::class . '@testException');
});

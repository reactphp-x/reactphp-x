<?php

use React\Http\HttpServer;
use React\Socket\SocketServer;
use ReactphpX\Route\Route;

$container = require __DIR__ . '/../bootstrap/app.php';

$route = new Route($container);

require __DIR__ . '/../routes/api.php';

$http = new HttpServer(
    new \App\Http\Middleware\Base\AccessLogHandler(),
    new \App\Http\Middleware\Base\ErrorHandler(),
    new \App\Http\Middleware\Base\FiberHandler(),
    new \App\Http\Middleware\Base\CrosMiddleware(),
    new \App\Http\Middleware\Base\TrustedProxyMiddleware(),
    $route
);

$listen = config('app.listen');
$socket = new SocketServer($listen);
$http->listen($socket);

echo "Server running at http://{$listen}\n";



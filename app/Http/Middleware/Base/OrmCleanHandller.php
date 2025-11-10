<?php

namespace App\Http\Middleware\Base;

use Psr\Http\Message\ServerRequestInterface;

class OrmCleanHandller
{
    private $orm;
    public function __construct()
    {
        $this->orm = app('orm');
    }
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        return \React\Promise\resolve($next($request))->then(function ($response) {
            orm()->getHeap()->clean();
            gc_collect_cycles();
            return $response;
        });
    }
}
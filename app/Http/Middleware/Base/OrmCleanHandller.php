<?php

namespace App\Http\Middleware\Base;

use Psr\Http\Message\ServerRequestInterface;

class OrmCleanHandller
{
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        return \React\Promise\resolve($next($request))->finally(function () {
            orm()->getHeap()->clean();
            gc_collect_cycles();
        });
    }
}
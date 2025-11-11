<?php

namespace App\Http\Middleware\Base;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;


class FiberHandler
{
    /**
     * @return ResponseInterface|PromiseInterface<ResponseInterface>|\Generator
     *     Returns a `ResponseInterface` from the next request handler in the
     *     chain. If the next request handler returns immediately, this method
     *     will return immediately. If the next request handler suspends the
     *     fiber (see `await()`), this method will return a `PromiseInterface`
     *     that is fulfilled with a `ResponseInterface` when the fiber is
     *     terminated successfully. If the next request handler returns a
     *     promise, this method will return a promise that follows its
     *     resolution. If the next request handler returns a Generator-based
     *     coroutine, this method returns a `Generator`. This method never
     *     throws or resolves a rejected promise. If the handler fails, it will
     *     be turned into a valid error response before returning.
     * @throws void
     */
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        return \React\Async\async(fn() => $next($request))();
    }
}

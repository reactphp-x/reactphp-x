<?php

namespace App\Http\Middleware\Base;

use App\Exceptions\BusinessException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use ReactphpX\Log\Log;
/**
 * @final
 */
class ErrorHandler
{
    public function __construct()
    {
    }

    /**
     * @return ResponseInterface|PromiseInterface<ResponseInterface>|\Generator
     *     Returns a response, a Promise which eventually fulfills with a
     *     response or a Generator which eventually returns a response. This
     *     method never throws or resolves a rejected promise. If the next
     *     handler fails to return a valid response, it will be turned into a
     *     valid error response before returning.
     * @throws void
     */
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            return $this->errorInvalidException($e);
        }

        if ($response instanceof ResponseInterface) {
            return $response;
        } elseif ($response instanceof PromiseInterface) {
            return $response->then(function ($response) {
                if ($response instanceof ResponseInterface) {
                    return $response;
                } else {
                    return $this->errorInvalidResponse($response);
                }
            }, function ($e) {
                // Promise rejected, always a `\Throwable` as of Promise v3
                assert($e instanceof \Throwable || !\method_exists(PromiseInterface::class, 'catch')); // @phpstan-ignore-line

                if ($e instanceof \Throwable) {
                    return $this->errorInvalidException($e);
                } else { // @phpstan-ignore-line
                    // @phpstan-ignore-next-line
                    return $this->errorInvalidResponse(\React\Promise\reject($e)); // @codeCoverageIgnore
                }
            });
        } elseif ($response instanceof \Generator) {
            return $this->coroutine($response);
        } else {
            return $this->errorInvalidResponse($response);
        }
    }

    private function coroutine(\Generator $generator): \Generator
    {
        do {
            try {
                if (!$generator->valid()) {
                    $response = $generator->getReturn();
                    if ($response instanceof ResponseInterface) {
                        return $response;
                    } else {
                        return $this->errorInvalidResponse($response);
                    }
                }
            } catch (\Throwable $e) {
                return $this->errorInvalidException($e);
            }

            $promise = $generator->current();
            if (!$promise instanceof PromiseInterface) {
                $gref = new \ReflectionGenerator($generator);

                return $this->errorInvalidCoroutine(
                    $promise,
                    $gref->getExecutingFile(),
                    $gref->getExecutingLine()
                );
            }

            try {
                $next = yield $promise;
            } catch (\Throwable $e) {
                try {
                    $generator->throw($e);
                    continue;
                } catch (\Throwable $e) {
                    return $this->errorInvalidException($e);
                }
            }

            try {
                $generator->send($next);
            } catch (\Throwable $e) {
                return $this->errorInvalidException($e);
            }
        } while (true);
    } // @codeCoverageIgnore

    /** @internal */
    public function requestNotFound(): ResponseInterface
    {
        return $this->htmlResponse(
            Response::STATUS_NOT_FOUND,
            'Page Not Found',
            'Please check the URL in the address bar and try again.'
        );
    }

    /**
     * @internal
     * @param list<string> $allowedMethods
     */
    public function requestMethodNotAllowed(array $allowedMethods): ResponseInterface
    {
        $methods = \implode('/', \array_map(function (string $method) { return '<code>' . $method . '</code>'; }, $allowedMethods));

        return $this->htmlResponse(
            Response::STATUS_METHOD_NOT_ALLOWED,
            'Method Not Allowed',
            'Please check the URL in the address bar and try again with ' . $methods . ' request.'
        )->withHeader('Allow', \implode(', ', $allowedMethods));
    }

    /** @internal */
    public function requestProxyUnsupported(): ResponseInterface
    {
        return $this->htmlResponse(
            Response::STATUS_BAD_REQUEST,
            'Proxy Requests Not Allowed',
            'Please check your settings and retry.'
        );
    }

    private function errorInvalidException(\Throwable $e): ResponseInterface
    {
        // 如果是商业异常，返回特定格式
        if ($e instanceof BusinessException) {
            $error = [
                'code' => $e->getCode() ?: 1,
                'msg' => $e->getMessage(),
            ];
           return Response::json($error);
        }

        // 其他异常保持原有处理逻辑
        $error = [
            'error' => [
                'type' => \get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]
        ];

        $json = \json_encode($error, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT);
        Log::channel('error')->error($e->getMessage(), $error);
        echo $json;
        return new Response(
            Response::STATUS_INTERNAL_SERVER_ERROR,
            ['Content-Type' => 'application/json; charset=utf-8'],
            $json
        );
    }

    /** @param mixed $value */
    private function errorInvalidResponse($value): ResponseInterface
    {
        return $this->htmlResponse(
            Response::STATUS_INTERNAL_SERVER_ERROR,
            'Internal Server Error',
            'The requested page failed to load, please try again later.',
            'Expected request handler to return <code>' . \htmlspecialchars(ResponseInterface::class, ENT_QUOTES, 'UTF-8') . '</code> but got <code>' . \htmlspecialchars($this->describeType($value), ENT_QUOTES, 'UTF-8') . '</code>.'
        );
    }

    /** @param mixed $value */
    private function errorInvalidCoroutine($value, string $file, int $line): ResponseInterface
    {
        $where = ' near or before '. $this->where($file, $line) . '.';

        return $this->htmlResponse(
            Response::STATUS_INTERNAL_SERVER_ERROR,
            'Internal Server Error',
            'The requested page failed to load, please try again later.',
            'Expected request handler to yield <code>' . \htmlspecialchars(PromiseInterface::class, ENT_QUOTES, 'UTF-8') . '</code> but got <code>' . \htmlspecialchars($this->describeType($value), ENT_QUOTES, 'UTF-8') . '</code>' . $where
        );
    }

    private function where(string $file, int $line): string
    {
        return '<code title="See ' . \htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . ' line ' . $line . '">' . \htmlspecialchars(\basename($file), ENT_QUOTES, 'UTF-8') . ':' . $line . '</code>';
    }

    private function htmlResponse(int $statusCode, string $title, string ...$info): ResponseInterface
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error ' . $statusCode . ': ' . \htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { margin: 0 0 20px 0; color: #333; }
        p { margin: 10px 0; color: #666; line-height: 1.5; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>' . \htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>
        ' . \implode('', \array_map(function (string $info) { return '<p>' . $info . '</p>'; }, $info)) . '
    </div>
</body>
</html>';

        return new Response(
            $statusCode,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html
        );
    }

    /** @param mixed $value */
    private function describeType($value): string
    {
        if ($value === null) {
            return 'null';
        } elseif (\is_scalar($value) && !\is_string($value)) {
            return \var_export($value, true);
        }
        return \is_object($value) ? \get_class($value) : \gettype($value);
    }
}

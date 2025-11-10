<?php

namespace App\Http\Middleware\Base;


// source https://github.com/zendframework/zend-http/blob/master/src/PhpEnvironment/RemoteAddress.php

class TrustedProxyMiddleware
{

    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request, callable $next)
    {
        return $next($request->withAttribute('remote_addr', $this->getIpAddress($request)));
    }

    protected  function getIpAddress($request)
    {
        $remote_addr = $request->getAttribute('remote_addr') ?? $request->getServerParams()['REMOTE_ADDR'] ?? null;

        $ip = $this->getIpAddressFromProxy($request, [
            $remote_addr,
            $request->getHeaderLine('X-REAL-IP'),
        ]);

        if ($ip) {
            return $ip;
        }

        return $remote_addr;
    }

    /**
     * Attempt to get the IP address for a proxied client
     *
     * @see http://tools.ietf.org/html/draft-ietf-appsawg-http-forwarded-10#section-5.2
     * @return false|string
     */
    protected function getIpAddressFromProxy($request, $trustedProxies = [])
    {
        $serverParams = $request->getServerParams();

        if (isset($serverParams['REMOTE_ADDR']) && ! in_array($serverParams['REMOTE_ADDR'], $trustedProxies)) {

            return false;
        }

        if (!$request->hasHeader('X-Forwarded-For')) {
            return false;
        }

        // Extract IPs
        $ips = explode(',', $request->getHeaderLine('X-Forwarded-For'));

        // trim, so we can compare against trusted proxies properly
        $ips = array_map('trim', $ips);
        // remove trusted proxy IPs
        $ips = array_diff($ips, $trustedProxies);
        // Any left?
        if (empty($ips)) {
            return false;
        }

        // Since we've removed any known, trusted proxy servers, the right-most
        // address represents the first IP we do not know about -- i.e., we do
        // not know if it is a proxy server, or a client. As such, we treat it
        // as the originating IP.
        // @see http://en.wikipedia.org/wiki/X-Forwarded-For
        $ip = array_pop($ips);
        return $ip;
    }

}
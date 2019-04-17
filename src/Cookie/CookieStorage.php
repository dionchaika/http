<?php

/**
 * The Psr Http Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\Cookie;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The cookie storage model.
 *
 * @see https://tools.ietf.org/html/rfc6265
 */
class CookieStorage
{
    /**
     * The array of cookies.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Store cookies.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return void
     * @throws \InvalidArgumentException
     */
    public function storeCookies(
        RequestInterface $request,
        ResponseInterface $response
    ): void {
        $requestUri = $request->getUri();
        $requestHost = $requestUri->getHost();
        $requestPath = $requestUri->getPath();

        if ('' === $requestHost) {
            throw new InvalidArgumentException(
                'Invalid request! Host is not defined.'
            );
        }

        $requestPath = ('' === $requestPath) ? '/' : '/'.ltrim($requestPath, '/');

        foreach ($response->getHeader('Set-Cookie') as $setCookie) {
            try {
                $cookie = Cookie::createFromString($setCookie);

                $name = $cookie->getName();
                $value = $cookie->getValue();
                $creationTime = $lastAccessTime = time();

                if (null !== $cookie->getMaxAge()) {
                    $persistent = true;
                    $expiryTime = time() + $cookie->getMaxAge();
                } else if (null !== $cookie->getExpires()) {
                    $persistent = true;
                    $expiryTime = strtotime($cookie->getExpires());
                } else {
                    $persistent = false;
                    $expiryTime = 0;
                }

                $domain = $cookie->getDomain() ?? '';
                if ('' !== $domain) {
                    if (!$cookie->isMatchesDomain($requestHost)) {
                        continue;
                    }

                    $hostOnly = false;
                } else {
                    $hostOnly = true;
                    $domain = $requestHost;
                }

                $path = $cookie->getPath();
                $path = (null === $path || '/' === $path) ? $requestPath : $path;

                $secureOnly = $cookie->getSecure();
                $httpOnly = $cookie->getHttpOnly();

                foreach ($this->cookies as $k => $v) {
                    if (
                        $v['domain'] === $domain &&
                        $v['path'] === $path &&
                        $v['name'] === $name
                    ) {
                        $creationTime = $v['creation_time'];
                        unset($this->cookies[$k]);
                    }
                }

                $this->cookies[] = [
                    'name' => $name,
                    'value' => $value,
                    'expiry_time' => $expiryTime,
                    'domain' => $domain,
                    'path' => $path,
                    'creation_time' => $creationTime,
                    'last_access_time' => $lastAccessTime,
                    'persistent' => $persistent,
                    'host_only' => $hostOnly,
                    'secure_only' => $secureOnly,
                    'http_only' => $httpOnly
                ];
            } catch (Exception $e) {}
        }
    }

    /**
     * Clear the cookies.
     *
     * @return void
     */
    public function clearCookies(): void
    {
        $this->cookies = [];
    }

    /**
     * Clear the expired cookies.
     *
     * @return void
     */
    public function clearExpiredCookies(): void
    {
        foreach ($this->cookies as $k => $v) {
            if (
                $v['persistent'] &&
                time() >= $v['expiry_time']
            ) {
                unset($this->cookies[$k]);
            }
        }
    }

    /**
     * Clear the session cookies.
     *
     * @return void
     */
    public function clearSessionCookies(): void
    {
        foreach ($this->cookies as $k => $v) {
            if (!$v['persistent']) {
                unset($this->cookies[$v]);
            }
        }
    }
}

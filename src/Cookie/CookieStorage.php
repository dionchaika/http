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

                //

            } catch (Exception $e) {}
        }
    }
}

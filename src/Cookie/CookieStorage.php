<?php

/**
 * The PSR HTTP Library.
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
     * @var mixed[]
     */
    protected $cookies = [];

    /**
     * Receive cookies from response.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return void
     * @throws \InvalidArgumentException
     */
    public function receiveFromResponse(RequestInterface $request, ResponseInterface $response): void
    {
        if ('' === $request->getUri()->getHost()) {
            throw new InvalidArgumentException(
                'Invalid request! Host is not defined.'
            );
        }

        if ('' === $request->getUri()->getPath()) {
            $request = $request->withUri(
                $request->getUri()->withPath('/')
            );
        } else {
            $request = $request->withUri(
                $request->getUri()->withPath('/'.ltrim($request->getUri()->getPath()))
            );
        }

        foreach ($response->getHeader('Set-Cookie') as $setCookie) {
            try {
                $cookie = Cookie::createFromString($setCookie);

                
            } catch (Exception $e) {}
        }
    }
}

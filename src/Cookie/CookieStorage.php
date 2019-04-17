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
     * Store cookies from request.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return void
     */
    public function storeFromRequest(RequestInterface $request)
    {
        foreach ($request->getHeader('Cookie') as $cookie) {
            try {
                $this->cookies[] = Cookie::createFromString($cookie);
            } catch (Exception $e) {}
        }
    }

    /**
     * Store cookies from response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return void
     */
    public function storeFromResponse(ResponseInterface $response)
    {
        foreach ($response->getHeader('Set-Cookie') as $setCookie) {
            try {
                $this->cookies[] = Cookie::createFromString($setCookie);
            } catch (Exception $e) {}
        }
    }
}

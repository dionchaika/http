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
                $storageAttributes = [

                    'name'             => null,
                    'value'            => null,
                    'expiry_time'      => null,
                    'domain'           => null,
                    'path'             => null,
                    'creation_time'    => null,
                    'last_access_time' => null,
                    'persistent'       => null,
                    'host_only'        => null,
                    'secure_only'      => null,
                    'http_only'        => null

                ];

                $cookie = Cookie::createFromString($setCookie);

                $storageAttributes['name'] = $cookie->getName();
                $storageAttributes['value'] = $cookie->getValue();
                $storageAttributes['creation_time'] = $storageAttributes['last_access_time'] = time();

                if (null !== $cookie->getMaxAge()) {
                    $storageAttributes['persistent'] = true;
                    $storageAttributes['expiry_time'] = $cookie->getMaxAge();
                } else if (null !== $cookie->getExpires()) {
                    $storageAttributes['persistent'] = true;
                    $storageAttributes['expiry_time'] = $cookie->getExpires();
                } else {
                    $storageAttributes['persistent'] = false;
                    $storageAttributes['expiry_time'] = -2147483648;
                }

                $domain = $cookie->getDomain() ?? '';
                if ('' !== $domain) {

                }

            } catch (Exception $e) {}
        }
    }

    /**
     * Check is the cookie path
     * matches a request URI path.
     *
     * @param string $cookiePath
     * @param string $requestUriPath
     * @return bool
     */
    protected function isMatchesPath(string $cookiePath, string $requestUriPath): bool
    {
        if ('/' === $cookiePath || $cookiePath === $requestUriPath) {
            return true;
        }

        if (0 !== strpos($requestUriPath, $cookiePath)) {
            return false;
        }

        if ('/' === substr($cookiePath, -1, 1)) {
            return true;
        }

        return '/' === substr($requestUriPath, strlen($cookiePath), 1);
    }

    /**
     * Check is the cookie domain
     * matches a request URI domain.
     *
     * @param string $cookieDomain
     * @param string $requestUriDomain
     * @return bool
     */
    protected function isMatchesDomain(string $cookieDomain, string $requestUriDomain): bool
    {
        if (0 === strcasecmp($cookieDomain, $requestUriDomain)) {
            return true;
        }

        if (filter_var($requestUriDomain, \FILTER_VALIDATE_IP)) {
            return false;
        }

        if (preg_match('/\.'.preg_quote($cookieDomain, '/').'$/', $requestUriDomain)) {
            return true;
        }

        return false;
    }
}

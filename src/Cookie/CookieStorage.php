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
use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The HTTP cookie storage model.
 *
 * @see https://tools.ietf.org/html/rfc6265
 */
class CookieStorage
{
    /**
     * The max cookies count.
     *
     * @var int
     */
    public $maxCookies = 3000;

    /**
     * The max cookies per domain count.
     *
     * @var int
     */
    public $maxCookiesPerDomain = 50;

    /**
     * The array of cookies.
     *
     * @var mixed[]
     */
    protected $cookies = [];

    /**
     * Get the array of cookies.
     *
     * @return mixed[]
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Clear all cookies.
     *
     * @return void
     */
    public function clearAllCookies(): void
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
        foreach ($this->cookies as $key => $value) {
            if (
                $value['persistent'] &&
                time() >= $value['expiry_time']
            ) {
                unset($this->cookies[$key]);
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
        foreach ($this->cookies as $key => $value) {
            if (!$value['persistent']) {
                unset($this->cookies[$key]);
            }
        }
    }

    /**
     * Clear the excess cookies.
     *
     * @return void
     */
    public function clearExcessCookies(): void
    {
        $this->clearExpiredCookies();

        //
    }

    /**
     * Load cookies from file.
     *
     * @param string $filename
     * @return void
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function loadCookies(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException(
                'File does not exists: '.$filename.'!'
            );
        }

        $contents = file_get_contents($filename);
        if (false === $contents) {
            throw new RuntimeException(
                'Unable to get the contents of the file: '.$filename.'!'
            );
        }

        $contentsParts = explode("\r\n", $contents);
        foreach ($contentsParts as $contentsPart) {
            $storageAttributes = explode(' ', $contentsPart);

            if (11 !== count($storageAttributes)) {
                continue;
            }

            $this->cookies[] = [

                'name'             => $storageAttributes['name'],
                'value'            => $storageAttributes['value'],
                'expiry_time'      => (int)$storageAttributes['expiry_time'],
                'domain'           => $storageAttributes['domain'],
                'path'             => $storageAttributes['path'],
                'creation_time'    => (int)$storageAttributes['creation_time'],
                'last_access_time' => (int)$storageAttributes['last_access_time'],
                'persistent'       => ('TRUE' === $storageAttributes['persistent']) ? true : false,
                'host_only'        => ('TRUE' === $storageAttributes['host_only']) ? true : false,
                'secure_only'      => ('TRUE' === $storageAttributes['secure_only']) ? true : false,
                'http_only'        => ('TRUE' === $storageAttributes['http_only']) ? true : false

            ];
        }
    }

    /**
     * Store cookies to file.
     *
     * @param string $filename
     * @return void
     * @throws \RuntimeException
     */
    public function storeCookies(string $filename): void
    {
        $contents = '';
        foreach ($this->cookies as $cookie) {
            $contents .= $cookie['name'];
            $contents .= " {$cookie['value']}";
            $contents .= " {$cookie['expiry_time']}";
            $contents .= " {$cookie['domain']}";
            $contents .= " {$cookie['path']}";
            $contents .= " {$cookie['creation_time']}";
            $contents .= " {$cookie['last_access_time']}";
            $contents .= " {${$cookie['persistent'] ? 'TRUE' : 'FALSE'}}";
            $contents .= " {${$cookie['host_only'] ? 'TRUE' : 'FALSE'}}";
            $contents .= " {${$cookie['secure_only'] ? 'TRUE' : 'FALSE'}}";
            $contents .= " {${$cookie['http_only'] ? 'TRUE' : 'FALSE'}}\r\n";
        }

        if (false === file_put_contents($filename, $contents)) {
            throw new RuntimeException(
                'Unable to store cookies to file: '.$filename.'!'
            );
        }
    }

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
                $request->getUri()->withPath('/'.ltrim($request->getUri()->getPath(), '/'))
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
                $storageAttributes['value'] = $cookie->getValue() ?? '';
                $storageAttributes['creation_time'] = $storageAttributes['last_access_time'] = time();

                if (null !== $cookie->getMaxAge()) {
                    $storageAttributes['persistent'] = true;
                    $storageAttributes['expiry_time'] = time() + $cookie->getMaxAge();
                } else if (
                    null !== $cookie->getExpires() &&
                    flase !== $expiryTime = strtotime($cookie->getExpires())
                ) {
                    $storageAttributes['persistent'] = true;
                    $storageAttributes['expiry_time'] = $expiryTime;
                } else {
                    $storageAttributes['persistent'] = false;
                    $storageAttributes['expiry_time'] = -2147483648;
                }

                if (-2147483648 > $storageAttributes['expiry_time']) {
                    $storageAttributes['expiry_time'] = -2147483648;
                } else if (2147483647 < $storageAttributes['expiry_time']) {
                    $storageAttributes['expiry_time'] = 2147483647;
                }

                $domain = $cookie->getDomain() ?? '';
                if ('' !== $domain) {
                    if (!$this->isMatchesDomain($domain, $request->getUri()->getHost())) {
                        continue;
                    }

                    $storageAttributes['host_only'] = false;
                    $storageAttributes['domain'] = $domain;
                } else {
                    $storageAttributes['host_only'] = true;
                    $storageAttributes['domain'] = $request->getUri()->getHost();
                }

                $storageAttributes['path'] = $cookie->getPath() ?? $request->getUri()->getPath();
                $storageAttributes['secure_only'] = $cookie->getSecure();
                $storageAttributes['http_only'] = $cookie->getHttpOnly();

                foreach ($this->cookies as $key => $value) {
                    if (
                        $value['name'] === $storageAttributes['name'] &&
                        $value['path'] === $storageAttributes['path'] &&
                        $value['domain'] === $storageAttributes['domain']
                    ) {
                        $storageAttributes['creation_time'] = $value['creation_time'];
                        unset($this->cookies[$key]);

                        break;
                    }
                }

                $this->cookies[] = $storageAttributes;
            } catch (Exception $e) {}
        }
    }

    /**
     * Include cookies to request.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \Psr\Http\Message\RequestInterface
     * @throws \InvalidArgumentException
     */
    public function includeToRequest(RequestInterface $request): RequestInterface
    {
        $scheme = $request->getUri()->getScheme();
        $scheme = ('' === $scheme) ? 'http' : $scheme;

        if ('' === $request->getUri()->getHost()) {
            throw new InvalidArgumentException(
                'Invalid request! Host is not defined.'
            );
        }

        $path = $request->getUri()->getPath();
        $path = ('' === $path) ? '/' : '/'.ltrim($path, '/');

        foreach ($this->cookies as $cookie) {
            if (
                $cookie['host_only'] &&
                0 !== strcasecmp($cookie['domain'], $request->getUri()->getHost())
            ) {
                continue;
            } else if (
                !$cookie['host_only'] &&
                !$this->isMatchesDomain($cookie['domain'], $request->getUri()->getHost())
            ) {
                continue;
            }

            if (!$this->isMatchesPath($cookie['path'], $path)) {
                continue;
            }

            if ($cookie['secure_only'] && 'https' !== $scheme) {
                continue;
            }

            $request = $request->withAddedHeader('Cookie', $cookie['name'].'='.$cookie['value']);
        }

        return $request;
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
     * @param string $requestUriHost
     * @return bool
     */
    protected function isMatchesDomain(string $cookieDomain, string $requestUriHost): bool
    {
        if (0 === strcasecmp($cookieDomain, $requestUriHost)) {
            return true;
        }

        if (filter_var($requestUriHost, \FILTER_VALIDATE_IP)) {
            return false;
        }

        if (preg_match('/\.'.preg_quote($cookieDomain, '/').'$/', $requestUriHost)) {
            return true;
        }

        return false;
    }
}

<?php

/**
 * The PSR HTTP Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * The PSR-7 URI model.
 *
 * @see https://www.php-fig.org/psr/psr-7/
 * @see https://www.ietf.org/rfc/rfc3986.txt
 */
class Uri implements UriInterface
{
    /**
     * The default ports.
     */
    const DEFAULT_PORTS = ['http' => 80, 'https' => 443];

    /**
     * The URI scheme.
     *
     * @var string
     */
    protected $scheme = '';

    /**
     * The URI user information.
     *
     * @var string
     */
    protected $userInfo = '';

    /**
     * The URI host.
     *
     * @var string
     */
    protected $host = '';

    /**
     * The URI port.
     *
     * @var int|null
     */
    protected $port;

    /**
     * The URI path.
     *
     * @var string
     */
    protected $path = '';

    /**
     * The URI query.
     *
     * @var string
     */
    protected $query = '';

    /**
     * The URI fragment.
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * @param string $uri
     * @throws \InvalidArgumentException
     */
    public function __construct($uri = '')
    {
        if ('' !== $uri) {
            $uriParts = parse_url($uri);
            if (false === $uriParts) {
                throw new InvalidArgumentException(
                    'Unable to parse the URI: '.$uri.'!'
                );
            }

            $scheme   = !empty($uriParts['scheme'])   ? $uriParts['scheme']   : '';
            $user     = !empty($uriParts['user'])     ? $uriParts['user']     : '';
            $password = !empty($uriParts['pass'])     ? $uriParts['pass']     : null;
            $host     = !empty($uriParts['host'])     ? $uriParts['host']     : '';
            $port     = !empty($uriParts['port'])     ? $uriParts['port']     : null;
            $path     = !empty($uriParts['path'])     ? $uriParts['path']     : '';
            $query    = !empty($uriParts['query'])    ? $uriParts['query']    : '';
            $fragment = !empty($uriParts['fragment']) ? $uriParts['fragment'] : '';

            $userInfo = $user;
            if ('' !== $userInfo && null !== $password && '' !== $password) {
                $userInfo .= ':'.$password;
            }

            $this->scheme   = $this->filterScheme($scheme);
            $this->userInfo = $userInfo;
            $this->host     = $this->filterHost($host);
            $this->port     = $this->filterPort($port);
            $this->path     = $this->filterPath($path);
            $this->query    = $this->filterQuery($query);
            $this->fragment = $this->filterFragment($fragment);
        }
    }

    /**
     * Create a new URI from globals.
     *
     * @return \Psr\Http\Message\UriInterface
     * @throws \InvalidArgumentException
     */
    public static function createFromGlobals()
    {
        $isSecured = !empty($_SERVER['HTTPS']) && 0 !== strcasecmp($_SERVER['HTTPS'], 'off');

        $scheme = $isSecured ? 'https' : 'http';
        $host = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : (!empty($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1');
        $port = !empty($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : ($isSecured ? 443 : 80);
        $path = !empty($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI'], 2)[0] : '/';
        $query = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

        return (new static)
            ->withScheme($scheme)
            ->withHost($host)
            ->withPort($port)
            ->withPath($path)
            ->withQuery($query);
    }

    /**
     * Get the URI scheme.
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Get the URI authority.
     *
     * @return string
     */
    public function getAuthority()
    {
        $authority = $this->host;

        if ('' !== $authority) {
            if ('' !== $this->userInfo) {
                $authority = $this->userInfo.'@'.$authority;
            }

            if (null !== $this->port) {
                $authority .= ':'.$this->port;
            }
        }

        return $authority;
    }

    /**
     * Get the URI user information.
     *
     * @return string
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * Get the URI host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the URI port.
     *
     * @return int|null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Get the URI path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the URI query.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the URI fragment.
     *
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Return an instance
     * with the specified URI scheme.
     *
     * @param string $scheme
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withScheme($scheme)
    {
        $new = clone $this;

        $new->scheme = $new->filterScheme($scheme);
        $new->port = $new->isNonStandartPort($new->scheme, $new->port) ? $new->port : null;

        return $new;
    }

    /**
     * Return an instance
     * with the specified URI user information.
     *
     * @param string $user
     * @param string|null $password
     * @return static
     */
    public function withUserInfo($user, $password = null)
    {
        $userInfo = $user;
        if ('' !== $userInfo && null !== $password && '' !== $password) {
            $userInfo .= ':'.$password;
        }

        $new = clone $this;
        $new->userInfo = $userInfo;

        return $new;
    }

    /**
     * Return an instance
     * with the specified URI host.
     *
     * @param string $host
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withHost($host)
    {
        $new = clone $this;
        $new->host = $new->filterHost($host);

        return $new;
    }

    /**
     * Return an instance
     * with the specified URI port.
     *
     * @param int|null $port
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withPort($port)
    {
        $new = clone $this;
        $new->port = $new->filterPort($port);

        return $new;
    }

    /**
     * Return an instance
     * with the specified URI path.
     *
     * @param string $path
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withPath($path)
    {
        $new = clone $this;
        $new->path = $new->filterPath($path);

        return $new;
    }

    /**
     * Return an instance
     * with the specified URI query.
     *
     * @param string $query
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withQuery($query)
    {
        $new = clone $this;
        $new->query = $new->filterQuery($query);

        return $new;
    }

    /**
     * Return an instance
     * with the specified URI query parameter.
     *
     * @param string $name
     * @param string $value
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withQueryParam($name, $value)
    {
        $new = clone $this;

        $queryParams = explode('&', $new->query);
        $queryParams[] = $name.'='.$value;

        $new->query = $new->filterQuery(implode('&', $queryParams));

        return $new;
    }

    /**
     * Return an instance
     * with the specified URI query parameters.
     *
     * @param mixed[] $params
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withQueryParams($params)
    {
        $new = clone $this;

        $queryParams = explode('&', $new->query);
        foreach ($params as $name => $value) {
            $queryParams[] = $name.'='.$value;
        }

        $new->query = $new->filterQuery(implode('&', $queryParams));

        return $new;
    }

    /**
     * Return an instance
     * with the specified URI fragment.
     *
     * @param string $fragment
     * @return static
     */
    public function withFragment($fragment)
    {
        $new = clone $this;
        $new->fragment = $new->filterFragment($fragment);

        return $new;
    }

    /**
     * Filter a URI scheme.
     *
     * @param string $scheme
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterScheme($scheme)
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException(
                'Invalid scheme! Scheme must be a string.'
            );
        }

        if ('' !== $scheme) {
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9+\-.]*$/', $scheme)) {
                throw new InvalidArgumentException(
                    'Invalid scheme! Scheme must be compliant with the "RFC 3986" standart.'
                );
            }

            return strtolower($scheme);
        }

        return $scheme;
    }

    /**
     * Filter a URI host.
     *
     * @param string $host
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterHost($host)
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException(
                'Invalid host! Host must be a string.'
            );
        }

        if ('' !== $host) {
            //
            // Matching an IPvFuture or an IPv6address.
            //
            if (preg_match('/^\[.+\]$/', $host)) {
                $host = trim($host, '[]');

                //
                // Matching an IPvFuture.
                //
                if (preg_match('/^(v|V)/', $host)) {
                    if (!preg_match('/^(v|V)[a-fA-F0-9]\.([a-zA-Z0-9\-._~]|[!$&\'()*+,;=]|\:)$/', $host)) {
                        throw new InvalidArgumentException(
                            'Invalid host! IP address must be compliant with the "IPvFuture" of the "RFC 3986" standart.'
                        );
                    }
                //
                // Matching an IPv6address.
                //
                } else if (false === filter_var($host, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
                    throw new InvalidArgumentException(
                        'Invalid host! IP address must be compliant with the "IPv6address" of the "RFC 3986" standart.'
                    );
                }

                $host = '['.$host.']';
            //
            // Matching an IPv4address.
            //
            } else if (preg_match('/^([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\./', $host)) {
                if (false === filter_var($host, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
                    throw new InvalidArgumentException(
                        'Invalid host! IP address must be compliant with the "IPv4address" of the "RFC 3986" standart.'
                    );
                }
            //
            // Matching a domain name.
            //
            } else {
                if (!preg_match('/^([a-zA-Z0-9\-._~]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=])*$/', $host)) {
                    throw new InvalidArgumentException(
                        'Invalid host! Host must be compliant with the "RFC 3986" standart.'
                    );
                }
            }

            return strtolower($host);
        }

        return $host;
    }

    /**
     * Filter a URI port.
     *
     * @param int|null $port
     * @return int|null
     * @throws \InvalidArgumentException
     */
    protected function filterPort($port)
    {
        if (null !== $port) {
            if (!is_int($port)) {
                throw new InvalidArgumentException(
                    'Invalid port! Port must be an integer.'
                );
            }

            if (1 > $port || 65535 < $port) {
                throw new InvalidArgumentException(
                    'Invalid port! TCP or UDP port must be between 1 and 65535.'
                );
            }

            return $this->isNonStandartPort($this->scheme, $port) ? $port : null;
        }

        return $port;
    }

    /**
     * Check if a URI port is non-standard for the given URI scheme.
     *
     * @param string $scheme
     * @param int|null $port
     * @return bool
     */
    protected function isNonStandartPort($scheme, $port)
    {
        return
            !isset(static::DEFAULT_PORTS[$scheme]) ||
            $port !== static::DEFAULT_PORTS[$scheme];
    }

    /**
     * Filter a URI path.
     *
     * @param string $path
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException(
                'Invalid path! Path must be a string.'
            );
        }

        if ('' === $this->scheme && 0 === strncmp($path, ':', 1)) {
            throw new InvalidArgumentException(
                'Invalid path! Path of a URI without a scheme cannot begin with a colon.'
            );
        }

        $authority = $this->getAuthority();

        if ('' === $authority && 0 === strncmp($path, '//', 2)) {
            throw new InvalidArgumentException(
                'Invalid path! Path of a URI without an authority cannot begin with two slashes.'
            );
        }

        if ('' !== $authority && '' !== $path && 0 !== strncmp($path, '/', 1)) {
            throw new InvalidArgumentException(
                'Invalid path! Path of a URI with an authority must be empty or begin with a slash.'
            );
        }

        if ('' !== $path && '/' !== $path) {
            if (!preg_match('/^([a-zA-Z0-9\-._~]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|\:|\@|\/|\%)*$/', $path)) {
                throw new InvalidArgumentException(
                    'Invalid path! Path must be compliant with the "RFC 3986" standart.'
                );
            }

            return preg_replace_callback('/(?:[^a-zA-Z0-9\-._~!$&\'()*+,;=:@\/%]++|%(?![a-fA-F0-9]{2}))/', function ($matches) {
                return rawurlencode($matches[0]);
            }, $path);
        }

        return $path;
    }

    /**
     * Filter a URI query.
     *
     * @param string $query
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterQuery($query)
    {
        if (!is_string($query)) {
            throw new InvalidArgumentException(
                'Invalid query! Query must be a string.'
            );
        }

        if ('' !== $query) {
            if (!preg_match('/^([a-zA-Z0-9\-._~]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|\:|\@|\/|\?|\%)*$/', $query)) {
                throw new InvalidArgumentException(
                    'Invalid query! Query must be compliant with the "RFC 3986" standart.'
                );
            }

            return preg_replace_callback('/(?:[^a-zA-Z0-9\-._~!$&\'()*+,;=:@\/?%]++|%(?![a-fA-F0-9]{2}))/', function ($matches) {
                return rawurlencode($matches[0]);
            }, $query);
        }

        return $query;
    }

    /**
     * Filter a URI fragment.
     *
     * @param string $fragment
     * @return string
     */
    protected function filterFragment($fragment)
    {
        if ('' !== $fragment) {
            return preg_replace_callback('/(?:[^a-zA-Z0-9\-._~!$&\'()*+,;=:@\/?%]++|%(?![a-fA-F0-9]{2}))/', function ($matches) {
                return rawurlencode($matches[0]);
            }, $fragment);
        }

        return $fragment;
    }

    /**
     * Return the string
     * representation of the URI.
     *
     * @return string
     */
    public function __toString()
    {
        $uri = '';

        if ('' !== $this->scheme) {
            $uri .= $this->scheme.':';
        }

        $authority = $this->getAuthority();
        if ('' !== $authority) {
            $uri .= '//'.$authority;
        }

        if ('' !== $authority && 0 !== strncmp($this->path, '/', 1)) {
            $uri .= '/'.$this->path;
        } else if ('' === $authority && 0 === strncmp($this->path, '//', 2)) {
            $uri .= '/'.ltrim($this->path, '/');
        } else {
            $uri .= $this->path;
        }

        if ('' !== $this->query) {
            $uri .= '?'.$this->query;
        }

        if ('' !== $this->fragment) {
            $uri .= '#'.$this->fragment;
        }

        return $uri;
    }
}

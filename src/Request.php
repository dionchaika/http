<?php

/**
 * The Psr Http Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;

class Request extends Message implements RequestInterface
{
    /**
     * The request target.
     *
     * @var string
     */
    protected $requestTarget;

    /**
     * The request method.
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * The request URI.
     *
     * @var \Psr\Http\Message\UriInterface
     */
    protected $uri;

    /**
     * @param string $method
     * @param \Psr\Http\Message\UriInterface|string|null $uri
     * @throws \InvalidArgumentException
     */
    public function __construct($method = 'GET', $uri = null)
    {
        $this->method = $this->filterMethod($method);

        if (null === $uri) {
            $uri = new Uri;
        } else if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        $this->uri = $uri;

        $this->headers['host'] = [
            'name' => 'Host',
            'values' => [$this->getHostHeader()]
        ];
    }

    /**
     * Get the request target.
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if (
            null !== $this->requestTarget &&
            '' !== $this->requestTarget
        ) {
            return $this->requestTarget;
        }

        if (null !== $this->uri) {
            $requestTarget = $this->uri->getPath();

            $query = $this->uri->getQuery();
            if ('' !== $query) {
                $requestTarget .= '?'.$query;
            }

            if ('' !== $requestTarget) {
                return $requestTarget;
            }
        }

        return '/';
    }

    /**
     * Return an instance
     * with the specified request target.
     *
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget)
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return an instance
     * with the specified request method.
     *
     * @param string $method
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withMethod($method)
    {
        $new = clone $this;
        $new->method = $new->filterMethod($method);

        return $new;
    }

    /**
     * Get the request URI.
     *
     * @return \Psr\Http\Message\UriInterface
     */
    public function getUri()
    {
        if (null === $this->uri) {
            $this->uri = new Uri;
        }

        return $this->uri;
    }

    /**
     * Return an instance
     * with the specified request URI.
     *
     * @param \Psr\Http\Message\UriInterface $uri
     * @param bool $preserveHost
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $new->hasHeader('Host')) {
            return $new;
        }

        return $new->withHeader('Host', $this->getHostHeader());
    }

    /**
     * Get the request Host header.
     *
     * @return string
     */
    protected function getHostHeader()
    {
        $host = $this->uri->getHost();
        if ('' !== $host) {
            $port = $this->uri->getPort();
            if (null !== $port) {
                $host .= ':'.$port;
            }
        }

        return $host;
    }

    /**
     * Filter a request method.
     *
     * @param string $method
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterMethod($method)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException(
                'Invalid method! Method must be a string.'
            );
        }

        if (!preg_match('/^[!#$%&\'*+\-.^_`|~0-9a-zA-Z]+$/', $method)) {
            throw new InvalidArgumentException(
                'Invalid method! Method must be compliant with the "RFC 7230" standart.'
            );
        }

        return $method;
    }

    /**
     * Return the string
     * representation of the request.
     *
     * @return string
     */
    public function __toString()
    {
        $request = "{$this->getMethod()} {$this->getRequestTarget()} HTTP/{$this->getProtocolVersion()}\r\n";
        foreach (array_keys($this->getHeaders()) as $header) {
            $request .= "{$header}: {$this->getHeaderLine($header)}\r\n";
        }

        return "{$request}\r\n{$this->getBody()}";
    }
}

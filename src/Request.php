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

/**
 * The HTTP response model.
 *
 * @see https://www.php-fig.org/psr/psr-7/
 * @see https://tools.ietf.org/html/rfc7230
 */
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
     * @param string                                     $method
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

        if ('1.1' === $this->getProtocolVersion()) {
            $this->headers['host'] = [
                'name' => 'Host',
                'values' => [$this->getHostHeader()]
            ];
        }
    }

    /**
     * Create a new request from string.
     *
     * @param string $request
     * @return \Dionchaika\Http\Request
     * @throws \InvalidArgumentException
     */
    public static function createFromString($request)
    {
        if (!is_string($request)) {
            throw new InvalidArgumentException(
                'Invalid request! Request must be a string.'
            );
        }

        if (false === strpos($request, "\r\n\r\n")) {
            throw new InvalidArgumentException(
                'Invalid request! Request must be compliant with the "RFC 7230" standart.'
            );
        }

        $requestParts = explode("\r\n\r\n", $request, 2);

        $headers = explode("\r\n", $requestParts[0]);
        $body = $requestParts[1];

        $requestLineParts = array_filter(explode(' ', array_shift($headers), 3));
        if (3 !== count($requestLineParts) || !preg_match('/^HTTP\/\d\.\d$/', $requestLineParts[2])) {
            throw new InvalidArgumentException(
                'Invalid request! Request must be compliant with the "RFC 7230" standart.'
            );
        }

        $method = $requestLineParts[0];
        $requestTarget = $requestLineParts[1];
        $protocolVersion = explode('/', $requestLineParts[2], 2)[1];

        $request = (new static($method))
            ->withoutHeader('Host')
            ->withRequestTarget($requestTarget)
            ->withProtocolVersion($protocolVersion);

        $request->getBody()->write($body);

        foreach ($headers as $header) {
            $headerParts = explode(':', $header, 2);

            $headerName = $headerParts[0];

            if ('cookie' === strtolower($headerName)) {
                $headerValues = array_map('trim', explode(';', $headerParts[1]));
            } else {
                $headerValues = array_map('trim', explode(',', $headerParts[1]));
            }

            $request = $request->withAddedHeader($headerName, $headerValues);
        }

        if ('1.1' === $protocolVersion && !$request->hasHeader('Host')) {
            throw new InvalidArgumentException(
                'Invalid request! "HTTP/1.1" request must contain a "Host" header.'
            );
        }

        return $request;
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
     * @return self
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
     * @return self
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
     * @param bool                           $preserveHost
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $new->hasHeader('Host')) {
            return $new;
        }

        return $new->withHeader('Host', $new->getHostHeader());
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
            if ('cookie' === strtolower($header)) {
                $cookie = implode('; ', $this->getHeader('Cookie'));
                $request .= "{$header}: {$cookie}\r\n";
            } else {
                $request .= "{$header}: {$this->getHeaderLine($header)}\r\n";
            }
        }

        return "{$request}\r\n{$this->getBody()}";
    }
}

<?php

/**
 * The Psr Http Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\Factory\Traits;

use Dionchaika\Http\Request;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Dionchaika\Http\FormData\FormData;
use Dionchaika\Http\XmlBuilder\XmlBuilder;

trait RequestFactoryTrait
{
    /**
     * Create a new request.
     *
     * @param string $method
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @return \Psr\Http\Message\RequestInterface
     * @throws \InvalidArgumentException
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    /**
     * Create a new text request.
     *
     * @param string $method
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param string $text
     * @return \Psr\Http\Message\RequestInterface
     * @throws \InvalidArgumentException
     */
    public function createTextRequest(string $method, $uri, string $text): RequestInterface
    {
        if ('GET' === $method || 'HEAD' === $method) {
            throw new InvalidArgumentException(
                'Request with a GET or HEAD method cannot contain a body'
            );
        }

        $request = (new Request($method, $uri))->withHeader('Content-Type', 'text/plain');
        $request->getBody()->write($text);

        $contentLength = $request->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $request = $request->withHeader('Content-Length', (string)$contentLength);
        }

        return $request;
    }

    /**
     * Create a new JSON request.
     *
     * @param string $method
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param array $data
     * @return \Psr\Http\Message\RequestInterface
     * @throws \InvalidArgumentException
     */
    public function createJsonRequest(string $method, $uri, array $data): RequestInterface
    {
        if ('GET' === $method || 'HEAD' === $method) {
            throw new InvalidArgumentException(
                'Request with a GET or HEAD method cannot contain a body'
            );
        }

        $json = json_encode($data);
        if (false === $json) {
            throw new InvalidArgumentException(
                'Unable to generate a JSON body!'
            );
        }

        $request = (new Request($method, $uri))->withHeader('Content-Type', 'application/json');
        $request->getBody()->write($json);

        $contentLength = $request->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $request = $request->withHeader('Content-Length', (string)$contentLength);
        }

        return $request;
    }

    /**
     * Create a new XML request.
     *
     * @param string $method
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param array $data
     * @return \Psr\Http\Message\RequestInterface
     * @throws \InvalidArgumentException
     */
    public function createXmlRequest(string $method, $uri, array $data): RequestInterface
    {
        if ('GET' === $method || 'HEAD' === $method) {
            throw new InvalidArgumentException(
                'Request with a GET or HEAD method cannot contain a body'
            );
        }

        $xml = XmlBuilder::createFromArray($data);

        $request = (new Request($method, $uri))->withHeader('Content-Type', 'text/xml');
        $request->getBody()->write($xml);

        $contentLength = $request->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $request = $request->withHeader('Content-Length', (string)$contentLength);
        }

        return $request;
    }

    /**
     * Create a new urlencoded request.
     *
     * @param string $method
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param array $data
     * @return \Psr\Http\Message\RequestInterface
     * @throws \InvalidArgumentException
     */
    public function createUrlencodedRequest(string $method, $uri, array $data): RequestInterface
    {
        if ('GET' === $method || 'HEAD' === $method) {
            throw new InvalidArgumentException(
                'Request with a GET or HEAD method cannot contain a body'
            );
        }

        $urlencoded = http_build_query($data);
        if (false === $urlencoded) {
            throw new InvalidArgumentException(
                'Unable to generate a urlencoded body!'
            );
        }

        $request = (new Request($method, $uri))->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request->getBody()->write($urlencoded);

        $contentLength = $request->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $request = $request->withHeader('Content-Length', (string)$contentLength);
        }

        return $request;
    }

    /**
     * Create a new form data request.
     *
     * @param string $method
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param \Dionchaika\Http\FormData\FormData $formData
     * @return \Psr\Http\Message\RequestInterface
     * @throws \InvalidArgumentException
     */
    public function createFormDataRequest(string $method, $uri, FormData $formData): RequestInterface
    {
        if ('GET' === $method || 'HEAD' === $method) {
            throw new InvalidArgumentException(
                'Request with a GET or HEAD method cannot contain a body'
            );
        }

        $boundary = $formData->getBoundary();

        $request = (new Request($method, $uri))->withHeader('Content-Type', 'multipart/form-data; boundary='.$boundary);
        $request->getBody()->write($formData);

        $contentLength = $request->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $request = $request->withHeader('Content-Length', (string)$contentLength);
        }

        return $request;
    }
}

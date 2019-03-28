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

use InvalidArgumentException;
use Dionchaika\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Dionchaika\Http\XmlBuilder\XmlBuilder;

trait ResponseFactoryTrait
{
    /**
     * Create a new response.
     *
     * @param int $code
     * @param string $reasonPhrase
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, $reasonPhrase);
    }

    /**
     * Create a new text response.
     *
     * @param string $text
     * @param int $code
     * @param string $reasonPhrase
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createTextResponse(string $text, int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = (new Response($code, $reasonPhrase))->withHeader('Content-Type', 'text/plain');
        $response->getBody()->write($text);

        $contentLength = $response->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $response = $response->withHeader('Content-Length', (string)$contentLength);
        }

        return $response;
    }

    /**
     * Create a new HTML response.
     *
     * @param string $html
     * @param int $code
     * @param string $reasonPhrase
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createHtmlResponse(string $html, int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = (new Response($code, $reasonPhrase))->withHeader('Content-Type', 'text/html');
        $response->getBody()->write($html);

        $contentLength = $response->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $response = $response->withHeader('Content-Length', (string)$contentLength);
        }

        return $response;
    }

    /**
     * Create a new JSON response.
     *
     * @param array $data
     * @param int $code
     * @param string $reasonPhrase
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createJsonResponse(array $data, int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $json = json_encode($data);
        if (false === $json) {
            throw new InvalidArgumentException(
                'Unable to generate a JSON body!'
            );
        }

        $response = (new Response($code, $reasonPhrase))->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($json);

        $contentLength = $response->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $response = $response->withHeader('Content-Length', (string)$contentLength);
        }

        return $response;
    }

    /**
     * Create a new XML response.
     *
     * @param array $data
     * @param int $code
     * @param string $reasonPhrase
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createXmlResponse(array $data, int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $xml = XmlBuilder::createFromArray($data);

        $response = (new Response($code, $reasonPhrase))->withHeader('Content-Type', 'text/xml');
        $response->getBody()->write($xml);

        $contentLength = $response->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $response = $response->withHeader('Content-Length', (string)$contentLength);
        }

        return $response;
    }

    /**
     * Create a new file response.
     *
     * @param string $filename
     * @param int $code
     * @param string $reasonPhrase
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createFileResponse(string $filename, int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        if (!is_file($filename)) {
            throw new InvalidArgumentException(
                'File does not exists: '.$filename.'!'
            );
        }

        $contentType = mime_content_type($filename);
        if (false === $contentType) {
            throw new InvalidArgumentException(
                'Unable to get a MIME-type of the file: '.$filename.'!'
            );
        }

        $fileContents = file_get_contents($filename);
        if (false === $fileContents) {
            throw new InvalidArgumentException(
                'Unable to get the contents of the file: '.$filename.'!'
            );
        }

        $response = (new Response($code, $reasonPhrase))->withHeader('Content-Type', $contentType);
        $response->getBody()->write($fileContents);

        $contentLength = $response->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $response = $response->withHeader('Content-Length', (string)$contentLength);
        }

        return $response;
    }

    /**
     * Create a new download response.
     *
     * @param string $filename
     * @param int $code
     * @param string $reasonPhrase
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createDownloadResponse(string $filename, int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        if (!is_file($filename)) {
            throw new InvalidArgumentException(
                'File does not exists: '.$filename.'!'
            );
        }

        $fileContents = file_get_contents($filename);
        if (false === $fileContents) {
            throw new InvalidArgumentException(
                'Unable to get the contents of the file: '.$filename.'!'
            );
        }

        $response = (new Response($code, $reasonPhrase))
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Disposition', 'attachment; filename="'.basename($filename).'"');

        $response->getBody()->write($fileContents);

        $contentLength = $response->getBody()->getSize();
        if (null !== $contentLength && 0 !== $contentLength) {
            $response = $response->withHeader('Content-Length', (string)$contentLength);
        }

        return $response;
    }

    /**
     * Create a new redirect response.
     *
     * @param string $location
     * @param int $code
     * @param string $reasonPhrase
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createRedirectResponse(string $location, int $code = 302, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response($code, $reasonPhrase))->withHeader('Location', $location);
    }
}

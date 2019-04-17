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
use Dionchaika\Http\Utils\XmlBuilder;
use Psr\Http\Message\ResponseInterface;

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

        return $this->assertResponseContentLengthHeader($response);
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

        return $this->assertResponseContentLengthHeader($response);
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

        return $this->assertResponseContentLengthHeader($response);
    }

    /**
     * Create a new XML response.
     *
     * @param array $data
     * @param string $encoding
     * @param int $code
     * @param string $reasonPhrase
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function createXmlResponse(array $data, string $encoding = 'utf-8', int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $xml = XmlBuilder::createFromArray($data, $encoding);

        $response = (new Response($code, $reasonPhrase))->withHeader('Content-Type', 'text/xml');
        $response->getBody()->write($xml);

        return $this->assertResponseContentLengthHeader($response);
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

        $type = mime_content_type($filename);
        if (false === $type) {
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

        $response = (new Response($code, $reasonPhrase))->withHeader('Content-Type', $type);
        $response->getBody()->write($fileContents);

        return $this->assertResponseContentLengthHeader($response);
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
        return $this->assertResponseContentLengthHeader($response);
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

    /**
     * Assert a response Content-Length header.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function assertResponseContentLengthHeader(ResponseInterface $response): ResponseInterface
    {
        $size = $response->getBody()->getSize();
        if (null !== $size && 0 !== $size) {
            return $response->withHeader('Content-Length', (string)$size);
        }

        return $response;
    }
}

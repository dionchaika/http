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
use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{
    /**
     * The reason phrases.
     */
    const REASON_PHRASES = [
        // Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // Successful
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',

        // Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    ];

    /**
     * The response status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * The response reason phrase.
     *
     * @var string
     */
    protected $reasonPhrase = 'OK';

    /**
     * @param int $code
     * @param string $reasonPhrase
     * @throws \InvalidArgumentException
     */
    public function __construct($code = 200, $reasonPhrase = '')
    {
        $this->statusCode = $this->filterStatusCode($code);

        if ('' === $reasonPhrase) {
            $reasonPhrase = isset(static::REASON_PHRASES[$this->statusCode])
                ? static::REASON_PHRASES[$this->statusCode]
                : '';
        }

        $this->reasonPhrase = $reasonPhrase;
    }

    /**
     * Create a new response from string.
     *
     * @param string $response
     * @return \Dionchaika\Http\Response
     * @throws \InvalidArgumentException
     */
    public static function createFromString($response)
    {
        if (!is_string($response)) {
            throw new InvalidArgumentException(
                'Invalid response! Response must be a string.'
            );
        }

        if (false === strpos($response, "\r\n\r\n")) {
            throw new InvalidArgumentException(
                'Invalid response! Response must be compliant with the "RFC 7230" standart.'
            );
        }

        $responseParts = explode("\r\n\r\n", $response, 2);

        $headers = explode("\r\n", $responseParts[0]);
        $body = $responseParts[1];

        $statusLineParts = array_filter(explode(' ', array_shift($headers), 3));
        if (2 > count($statusLineParts) || !preg_match('/^HTTP\/\d\.\d$/', $statusLineParts[0])) {
            throw new InvalidArgumentException(
                'Invalid response! Response must be compliant with the "RFC 7230" standart.'
            );
        }

        $protocolVersion = explode('/', $statusLineParts[0], 2)[1];
        $statusCode = (int)$statusLineParts[1];
        $reasonPhrase = isset($statusLineParts[2]) ? $statusLineParts[2] : '';

        $response = (new static($statusCode, $reasonPhrase))->withProtocolVersion($protocolVersion);
        $response->getBody()->write($body);

        foreach ($headers as $header) {
            $headerParts = explode(':', $header, 2);

            $headerName = $headerParts[0];
            if ('set-cookie' === strtolower($headerName)) {
                $headerValues = $headerParts[1];
            } else {
                $headerValues = array_map('trim', explode(',', $headerParts[1]));
            }

            $response = $response->withAddedHeader($headerName, $headerValues);
        }

        return $response;
    }

    /**
     * Get the response status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Return an instance
     * with the specified response status.
     *
     * @param int $code
     * @param string $reasonPhrase
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $new = clone $this;
        $new->statusCode = $new->filterStatusCode($code);

        if ('' === $reasonPhrase) {
            $reasonPhrase = isset(static::REASON_PHRASES[$new->statusCode])
                ? static::REASON_PHRASES[$new->statusCode]
                : '';
        }

        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    /**
     * Get the response reason phrase.
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * Filter a response status code.
     *
     * @param int $code
     * @return int
     * @throws \InvalidArgumentException
     */
    protected function filterStatusCode($code)
    {
        if (!is_int($code)) {
            throw new InvalidArgumentException(
                'Invalid status code! Status code must be an integer.'
            );
        }

        if (306 === $code) {
            throw new InvalidArgumentException(
                'Invalid status code! Status code 306 is unused.'
            );
        }

        if (100 > $code || 599 < $code) {
            throw new InvalidArgumentException(
                'Invalid status code! Status code must be between 100 and 599.'
            );
        }

        return $code;
    }

    /**
     * Return the string
     * representation of the response.
     *
     * @return string
     */
    public function __toString()
    {
        $response = "HTTP/{$this->getProtocolVersion()} {$this->getStatusCode()} {$this->getReasonPhrase()}\r\n";
        foreach (array_keys($this->getHeaders()) as $header) {
            if ('set-cookie' === strtolower($header)) {
                foreach ($this->getHeader('Set-Cookie') as $setCookie) {
                    $response .= "{$header}: {$setCookie}\r\n";
                }
            } else {
                $response .= "{$header}: {$this->getHeaderLine($header)}\r\n";
            }
        }

        return "{$response}\r\n{$this->getBody()}";
    }
}

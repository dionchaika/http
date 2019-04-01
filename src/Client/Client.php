<?php

/**
 * The Psr Http Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\Client;

use Exception;
use Dionchaika\Http\Uri;
use Dionchaika\Http\Response;
use Dionchaika\Http\Cookie\Cookie;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client implements ClientInterface
{
    /**
     * The array of client config.
     *
     * @var array
     */
    protected $config = [
        'headers' => [],
        'cookies' => true,
        'cookies_file' => null,
        'timeout' => 30.0,
        'redirects' => false,
        'max_redirects' => 10,
        'context' => null,
        'context_opts' => [],
        'context_params' => [],
        'debug' => false,
        'debug_file' => null,
        'debug_request_body' => false,
        'debug_response_body' => false
    ];

    /**
     * The array of client cookies.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * The client redirects count.
     *
     * @var int
     */
    protected $redirectsCount = 0;

    /**
     * The array of client redirects history.
     *
     * @var array
     */
    protected $redirectsHistory = [];

    /**
     * @param array $config
     */
    public function __construct(array $config = []) {
        if (isset($config['headers']) && is_array($config['headers'])) {
            $this->config['headers'] = $config['headers'];
        }

        if (isset($config['cookies']) && is_bool($config['cookies'])) {
            $this->config['cookies'] = $config['cookies'];
        }

        if (isset($config['cookies_file']) && is_string($config['cookies_file'])) {
            $this->config['cookies_file'] = $config['cookies_file'];
        }

        if (isset($config['timeout']) && is_float($config['timeout'])) {
            $this->config['timeout'] = $config['timeout'];
        }

        if (isset($config['redirects']) && is_bool($config['redirects'])) {
            $this->config['redirects'] = $config['redirects'];
        }

        if (isset($config['max_redirects']) && is_int($config['max_redirects'])) {
            $this->config['max_redirects'] = $config['max_redirects'];
        }

        if (isset($config['context']) && is_resource($config['context'])) {
            $this->config['context'] = $config['context'];
        }

        if (isset($config['context_opts']) && is_array($config['context_opts'])) {
            $this->config['context_opts'] = $config['context_opts'];
        }

        if (isset($config['context_params']) && is_array($config['context_params'])) {
            $this->config['context_params'] = $config['context_params'];
        }

        if (isset($config['debug']) && is_bool($config['debug'])) {
            $this->config['debug'] = $config['debug'];
        }

        if (isset($config['debug_file']) && is_string($config['debug_file'])) {
            $this->config['debug_file'] = $config['debug_file'];
        }

        if (isset($config['debug_request_body']) && is_bool($config['debug_request_body'])) {
            $this->config['debug_request_body'] = $config['debug_request_body'];
        }

        if (isset($config['debug_response_body']) && is_bool($config['debug_response_body'])) {
            $this->config['debug_response_body'] = $config['debug_response_body'];
        }
    }

    /**
     * Get the array of client cookies.
     *
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Clear the array of client cookies.
     *
     * @return void
     */
    public function clearCookies(): void
    {
        $this->cookies = [];
    }

    /**
     * Clear session cookies
     * from the array of client cookies.
     *
     * @return void
     */
    public function clearSessionCookies(): void
    {
        //
    }

    /**
     * Get the array of client redirects history.
     *
     * @return array
     */
    public function getRedirectsHistory(): array
    {
        return $this->redirectsHistory;
    }

    /**
     * Clear the array of client redirects history.
     *
     * @return void
     */
    public function clearRedirectsHistory(): void
    {
        $this->redirectsHistory = [];
    }

    /**
     * Send a request and return a response.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request = $this->prepareRequestHeaders($request);

        $uri = $request->getUri();

        $scheme = $uri->getScheme();
        $scheme = ('' === $scheme) ? 'http' : $scheme;

        $host = $uri->getHost();
        if ('' === $host) {
            throw new RequestException(
                $request,
                'Invalid request! Host is not defined.'
            );
        }

        $port = $uri->getPort();
        $port = $port ?? (('https' === $scheme) ? 443 : 80);

        $transportProtocol = ('https' === $scheme) ? 'ssl' : 'tcp';

        try {
            $socket = $this->socketConnect($transportProtocol, $host, $port);
        } catch (Exception $e) {
            throw new NetworkException($request, $e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        $requestMessage = '';

        $method = $request->getMethod();
        $method = ('' === $method) ? 'GET' : $method;

        $requestTarget = $request->getRequestTarget();

        $protocolVersion = $request->getProtocolVersion();
        $protocolVersion = ('' === $protocolVersion) ? '1.1' : $protocolVersion;

        $body = $request->getBody();
        $size = $body->getSize();

        if (null !== $size && 0 !== $size) {
            if ('GET' === $method || 'HEAD' === $method) {
                throw new RequestException(
                    $request,
                    'Invalid request! Request with a GET or HEAD method cannot contain a body.'
                );
            }

            if (!$body->isReadable()) {
                throw new RequestException(
                    $request,
                    'Invalid request! Body is not readable.'
                );
            }

            if (!$request->hasHeader('Content-Length')) {
                $request = $request->withHeader('Content-Length', (string)$size);
            }
        }

        $requestMessage .= "{$method} {$requestTarget} HTTP/{$protocolVersion}\r\n";

        foreach (array_keys($request->getHeaders()) as $name) {
            $requestMessage .= "{$name}: {$request->getHeaderLine($name)}\r\n";
        }

        if ($this->config['debug']) {
            $message = '';

            foreach (explode("\r\n", $requestMessage) as $line) {
                $message .= "|| -> {$line}\r\n";
            }

            $message .= "|| \r\n";

            if (null !== $this->config['debug_file']) {
                try {
                    file_put_contents($this->config['debug_file'], $message, \FILE_APPEND);
                } catch (Exception $e) {}
            } else {
                echo $message;
            }
        }

        $requestMessage .= "\r\n{$body}";

        try {
            $this->writeSocket($socket, $requestMessage);
        } catch (Exception $e) {
            throw new NetworkException($request, $e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        try {
            $responseMessage = $this->readSocket($socket);
        } catch (Exception $e) {
            throw new NetworkException($request, $e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        try {
            $this->socketDisconnect($socket);
        } catch (Exception $e) {
            throw new ClientException($request, $e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        $responseMessageParts = explode("\r\n\r\n", $responseMessage, 2);

        if ($this->config['debug']) {
            $message = '';

            foreach (explode("\r\n", $responseMessageParts[0]) as $line) {
                $message .= "|| <- {$line}\r\n";
            }

            $message .= "|| <- \r\n\r\n";

            if (null !== $this->config['debug_file']) {
                try {
                    file_put_contents($this->config['debug_file'], $message, \FILE_APPEND);
                } catch (Exception $e) {}
            } else {
                echo $message;
            }
        }

        $headers = explode("\r\n", $responseMessageParts[0]);
        $body = $responseMessageParts[1];

        $responseLineParts = explode(' ', array_shift($headers), 3);

        $protocolVersion = explode('/', trim($responseLineParts[0]), 2)[1];
        $statusCode = (int)trim($responseLineParts[1]);
        $reasonPhrase = isset($responseLineParts[2]) ? trim($responseLineParts[2]) : '';

        $response = (new Response($statusCode, $reasonPhrase))->withProtocolVersion($protocolVersion);
        $response->getBody()->write($body);

        foreach ($headers as $header) {
            $headerParts = explode(':', $header, 2);

            $headerName = trim($headerParts[0]);
            $headerValues = array_map('trim', explode(',', $headerParts[1]));

            if ('set-cookie' === strtolower($headerName) && $this->config['cookies']) {
                try {
                    $cookie = Cookie::createFromString($headerParts[1]);
                    $this->cookies[$cookie->getName()] = $cookie;
                } catch (Exception $e) {}
            }

            $response = $response->withAddedHeader($headerName, $headerValues);
        }

        if (preg_match('/(gzip|deflate)/', $response->getHeaderLine('Content-Encoding'))) {
            if (extension_loaded('zlib')) {
                $decodedBody = zlib_decode($response->getBody());
                if (false !== $decodedBody) {
                    $response->getBody()->rewind();
                    $response->getBody()->write($decodedBody);

                    $response = $response->withoutHeader('Content-Encoding');
                }
            } else {
                trigger_error(
                    "Server requested with an encoded body.\r\n"
                    ."Client requires a \"Zlib\" extension to encode the body automaticly.\r\n"
                );
            }
        }

        if (
            (201 === $statusCode || (300 < $statusCode && 400 > $statusCode)) &&
            $response->hasHeader('Location') &&
            $this->config['redirects'] &&
            $this->redirectsCount <= $this->config['max_redirects']
        ) {
            if (303 === $statusCode) {
                $request = $request->withMethod('GET');
                $request->getBody()->write('');
            }

            $redirectUri = new Uri($response->getHeaderLine('Location'));
            if ('' === $redirectUri->getHost()) {
                $redirectUri = $redirectUri->withHost($host);
            }

            $this->redirectsHistory[] = [
                'uri' => $redirectUri,
                'headers' => $response->getHeaders()
            ];

            ++$this->redirectsCount;

            $response = $this->sendRequest($request->withUri($redirectUri));
        } else {
            $this->redirectsCount = 0;
        }

        return $response;
    }

    /**
     * Send an XHR request and return a response.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendXhrRequest(RequestInterface $request): ResponseInterface
    {
        return $this->sendRequest($request->withHeader('X-Requested-With', 'XMLHttpRequest'));
    }

    /**
     * Prepare a request headers.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function prepareRequestHeaders(RequestInterface $request): RequestInterface
    {
        foreach ($this->config['headers'] as $name => $value) {
            $request = $request->withAddedHeader($name, $value);
        }

        if (!$request->hasHeader('Connection')) {
            $request = $request->withHeader('Connection', 'close');
        }

        if ($this->config['cookies']) {
            $cookies = [];
            foreach ($this->cookies as $cookie) {
                $cookies[] = $cookie->getNameValuePair();
            }

            if (!empty($cookies)) {
                $request = $request->withHeader('Cookie', implode('; ', $cookies));
            }
        }

        return $request;
    }

    /**
     * Connect to a remote socket.
     *
     * @param string $transportProtocol
     * @param string $host
     * @param int    $port
     * @return resource
     * @throws \Exception
     */
    protected function socketConnect(string $transportProtocol, string $host, int $port)
    {
        $remoteSocket = $transportProtocol.'://'.$host.':'.$port;

        $timeout = $this->config['timeout'];

        if (null !== $this->config['context']) {
            $context = $this->config['context'];
        } else {
            $contextOpts = $this->config['context_opts'];
            $contextParams = $this->config['context_params'];

            $context = stream_context_create($contextOpts, $contextParams);
        }

        $socket = stream_socket_client($remoteSocket, $errno, $errstr, $timeout, \STREAM_CLIENT_CONNECT, $context);
        if (false === $socket) {
            throw new Exception(
                'Remote socket connection error #'.$errno.'! '.$errstr.'.'
            );
        }

        $timeoutParts = explode('.', (string)$timeout, 2);

        $timeoutSecs = (int)$timeoutParts[0];
        $timeoutMicrosecs = isset($timeoutParts[1]) ? (int)$timeoutParts[1] : 0;

        if (false === stream_set_timeout($socket, $timeoutSecs, $timeoutMicrosecs)) {
            throw new Exception(
                'Unable to set the remote socket timeout!'
            );
        }

        if ($this->config['debug']) {
            $message = "|| *  {$remoteSocket}\r\n|| \r\n";
            if (null !== $this->config['debug_file']) {
                try {
                    file_put_contents($this->config['debug_file'], $message, \FILE_APPEND);
                } catch (Exception $e) {}
            } else {
                echo $message;
            }
        }

        return $socket;
    }

    /**
     * Write data to the socket.
     *
     * @param resource $socket
     * @param string   $data
     * @return void
     * @throws \Exception
     */
    protected function writeSocket($socket, string $data): void
    {
        if (false === fwrite($socket, $data)) {
            throw new Exception(
                'Unable to write data to the socket!'
            );
        }
    }

    /**
     * Read data from the socket.
     *
     * @param resource $socket
     * @return string
     * @throws \Exception
     */
    protected function readSocket($socket): string
    {
        $data = stream_get_contents($socket);

        $meta = stream_get_meta_data($socket);
        if (!empty($meta['timed_out']) && true === $meta['timed_out']) {
            throw new Exception(
                'Socket connection timed out!'
            );
        }

        if (false === $data) {
            throw new Exception(
                'Unable to read data from the socket!'
            );
        }

        return $data;
    }

    /**
     * Disconnect from the remote socket.
     *
     * @param resource $socket
     * @return void
     * @throws \Exception
     */
    protected function socketDisconnect($socket): void
    {
        if (false === fclose($socket)) {
            throw new Exception(
                'Unable to disconnect from the remote socket!'
            );
        }
    }
}

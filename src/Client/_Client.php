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

use Throwable;
use Dionchaika\Http\Uri;
use Dionchaika\Http\Stream;
use Dionchaika\Http\Response;
use Dionchaika\Http\Cookie\Cookie;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class _Client implements ClientInterface
{
    /**
     * @var mixed[]
     */
    protected $config = [

        'headers'             => [],
        'cookies'             => true,
        'cookies_file'        => null,
        'timeout'             => 30.0,
        'redirects'           => false,
        'max_redirects'       => 10,
        'strict_redirects'    => true,
        'referer_header'      => true,
        'redirects_history'   => true,
        'receive_body'        => true,
        'unchunk_body'        => true,
        'decode_body'         => true,
        'context'             => null,
        'context_opts'        => [],
        'context_params'      => [],
        'debug'               => false,
        'debug_file'          => null,
        'debug_request_body'  => false,
        'debug_response_body' => false

    ];

    /**
     * @var \Dionchaika\Http\Cookie\Cookie[]
     */
    protected $cookies = [];

    /**
     * @var int
     */
    protected $redirectsCount = 0;

    /**
     * @var mixed[]
     */
    protected $redirectsHistory = [];

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->setConfig($config);
        }
    }

    /**
     * @return \Dionchaika\Http\Cookie\Cookie[]
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @return void
     */
    public function clearCookies(): void
    {
        $this->cookies = [];
    }

    /**
     * @return void
     */
    public function clearExpiredCookies(): void
    {
        foreach ($this->cookies as $key => $value) {
            if ($value->isExpired()) {
                unset($this->cookies[$key]);
            }
        }
    }

    /**
     * @return void
     */
    public function clearSessionCookies(): void
    {
        foreach ($this->cookies as $key => $value) {
            if (
                null === $value->getExpires() &&
                null === $value->getMaxAge()
            ) {
                unset($this->cookies[$key]);
            }
        }
    }

    /**
     * @return mixed[]
     */
    public function getRedirectsHistory(): array
    {
        return $this->redirectsHistory;
    }

    /**
     * @return void
     */
    public function clearRedirectsHistory(): void
    {
        $this->redirectsHistory = [];
    }

    /**
     * @param mixed[] $config
     * @return void
     */
    public function setConfig(array $config): void
    {
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

        if (isset($config['strict_redirects']) && is_bool($config['strict_redirects'])) {
            $this->config['strict_redirects'] = $config['strict_redirects'];
        }

        if (isset($config['referer_header']) && is_bool($config['referer_header'])) {
            $this->config['referer_header'] = $config['referer_header'];
        }

        if (isset($config['redirects_history']) && is_bool($config['redirects_history'])) {
            $this->config['redirects_history'] = $config['redirects_history'];
        }

        if (isset($config['receive_body']) && is_bool($config['receive_body'])) {
            $this->config['receive_body'] = $config['receive_body'];
        }

        if (isset($config['unchunk_body']) && is_bool($config['unchunk_body'])) {
            $this->config['unchunk_body'] = $config['unchunk_body'];
        }

        if (isset($config['decode_body']) && is_bool($config['decode_body'])) {
            $this->config['decode_body'] = $config['decode_body'];
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
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        foreach ($this->config['headers'] as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ('' === $request->getProtocolVersion()) {
            $request = $request->withProtocolVersion('1.1');
        }

        if (
            '1.1' === $request->getProtocolVersion() &&
            !$request->hasHeader('Connection')
        ) {
            $request = $request->withHeader('Connection', 'close');
        }

        if ('' === $request->getUri()->getScheme()) {
            $request = $request->withUri(
                $request->getUri()->withScheme('https')
            );
        }

        if ('' === $request->getUri()->getHost()) {
            throw new RequestException(
                $request,
                'Invalid request! Host is not defined.'
            );
        }

        $port = $request->getUri()->getPort();
        $port = $port ?? (('https' === $request->getUri()->getScheme()) ? 443 : 80);

        $transportProtocol = ('https' === $request->getUri()->getScheme()) ? 'ssl' : 'tcp';
        $remoteSocket = "{$transportProtocol}://{$request->getUri()->getHost()}:{$port}";

        if (null !== $this->config['context']) {
            $context = $this->config['context'];
        } else {
            $contextOpts = $this->config['context_opts'];
            $contextParams = $this->config['context_params'];

            $context = stream_context_create($contextOpts, $contextParams);
        }

        $socket = stream_socket_client(
            $remoteSocket,
            $errno,
            $errstr,
            $this->config['timeout'],
            \STREAM_CLIENT_CONNECT,
            $context
        );

        if (false === $socket) {
            throw new NetworkException(
                $request,
                'Remote socket connection error #'.$errno.'! '.$errstr.'.'
            );
        }

        $timeoutParts = explode('.', (string)$this->config['timeout']);

        $timeoutSecs = (int)$timeoutParts[0];
        $timeoutMicrosecs = isset($timeoutParts[1]) ? (int)$timeoutParts[1] : 0;

        if (false === stream_set_timeout($socket, $timeoutSecs, $timeoutMicrosecs)) {
            throw new ClientException(
                $request,
                'Unable to set the remote socket timeout!'
            );
        }

        $this->debugConnection($remoteSocket);

        if ($this->config['cookies']) {
            foreach ($this->cookies as $key => $value) {
                if ($value->isExpired()) {
                    unset($this->cookies[$key]);
                    continue;
                }

                if (!$value->isMatchesDomain($request->getHost())) {
                    continue;
                }

                if (!$value->isMatchesPath($request->getPath())) {
                    continue;
                }

                $request = $request->withAddedHeader('Cookie', $value->getNameValuePair());
            }
        }

        if (
            null !== $request->getBody()->getSize() &&
            0 !== $request->getBody()->getSize()
        ) {
            if (
                'GET' === $request->getMethod() ||
                'HEAD' === $request->getMethod()
            ) {
                throw new RequestException(
                    $request,
                    'Invalid request! Request with a GET or HEAD method cannot contain a body.'
                );
            }

            if (!$request->getBody()->isReadable()) {
                throw new RequestException(
                    $request,
                    'Invalid request! Body is not readable.'
                );
            }

            if (!$request->hasHeader('Content-Length')) {
                $request = $request->withHeader('Content-Length', (string)$$request->getBody()->getSize());
            }
        }

        if (false === fwrite($socket, (string)$request)) {
            throw new ClientException(
                $request,
                'Unable to write data to the socket!'
            );
        }

        $this->debugRequest($request);

        if ($this->config['receive_body']) {
            $response = stream_get_contents($socket);
            if (false === $response) {
                throw new ClientException(
                    $request,
                    'Unable to read data from the socket!'
                );
            }
        } else {
            $response = '';
            while (!feof($socket)) {
                $response .= fread($socket, 1);

                if ("\r\n\r\n" === substr($response, -4, 4)) {
                    break;
                }
            }
        }

        $meta = stream_get_meta_data($socket);
        if (!empty($meta['timed_out']) && true === $meta['timed_out']) {
            throw new NetworkException(
                $request,
                'Socket connection timed out!'
            );
        }

        $response = Response::createFromString($response);

        print_r($response); die;

        if ($this->config['cookies']) {
            foreach ($response->getHeader('Set-Cookie') as $setCookie) {
                try {
                    $cookie = Cookie::createFromString($setCookie);

                    foreach ($this->cookies as $key => $value) {
                        if (
                            $value->getName() === $cookie->getName() &&
                            $value->getPath() === $cookie->getPath() &&
                            $value->getDomain() === $cookie->getDomain()
                        ) {
                            if ($cookie->isExpired()) {
                                unset($this->cookies[$key]);
                            } else {
                                $this->cookies[$key] = $cookie;
                            }

                            break;
                        }
                    }

                    $this->cookies[] = $cookie;
                } catch (Throwable $e) {}
            }
        }

        if (
            $response->hasHeader('Transfer-Encoding') &&
            'chunked' === $response->getHeaderLine('Transfer-Encoding') &&
            $this->config['unchunk_body']
        ) {
            $response = $response
                ->withBody(
                    new Stream($this->unchunkString((string)$response->getBody()))
                );

            $size = $response->getBody()->getSize();
            if (null !== $size && 0 !== $size) {
                $response = $response->withHeader('Content-Length', (string)$size);
            }

            $response = $response->withoutHeader('Transfer-Encoding');
        }

        if (
            $response->hasHeader('Content-Encoding') && (
                'gzip' === $response->getHeaderLine('Content-Encoding') ||
                'deflate' === $response->getHeaderLine('Content-Encoding') ||
                'compress' === $response->getHeaderLine('Content-Encoding')
            ) && $this->config['decode_body']
        ) {
            switch ($response->getHeaderLine('Content-Encoding')) {
                case 'gzip':
                    $response = $response->withBody(
                        new Stream($this->ungzipString((string)$response->getBody()))
                    );
                    break;
                case 'deflate':
                    $response = $response->withBody(
                        new Stream($this->undeflateString((string)$response->getBody()))
                    );
                    break;
                case 'compress':
                    $response = $response->withBody(
                        new Stream($this->uncompressString((string)$response->getBody()))
                    );
                    break;
            }

            $size = $response->getBody()->getSize();
            if (null !== $size && 0 !== $size) {
                $response = $response->withHeader('Content-Length', (string)$size);
            }

            $response = $response->withoutHeader('Content-Encoding');
        }

        $this->debugResponse($response);

        if (
            (201 === $response->getStatusCode() || (300 < $response->getStatusCode() && 400 > $response->getStatusCode())) &&
            $response->hasHeader('Location') &&
            $this->config['redirects'] &&
            $this->redirectsCount <= $this->config['max_redirects']
        ) {
            if (
                303 === $response->getStatusCode() ||
                !$this->config['strict_redirects']
            ) {
                $request = $request
                    ->withMethod('GET')
                    ->withBody(new Stream);
            }

            $redirectUri = new Uri($response->getHeaderLine('Location'));
            if ('' === $redirectUri->getHost()) {
                $redirectUri = $redirectUri->withHost($request->getUri()->getHost());
            }

            if ($this->config['redirects_history']) {
                $this->redirectsHistory[] = [
                    'uri' => $redirectUri,
                    'headers' => $response->getHeaders()
                ];
            }

            if ($this->config['referer_header']) {
                $request = $request->withHeader('Referer', $request->getUri());
            }

            ++$this->redirectsCount;

            $response = $this->sendRequest($request->withUri($redirectUri));
        } else {
            $this->redirectsCount = 0;
        }

        return $response;
    }

    /**
     * @param string $remoteSocket
     * @return void
     */
    protected function debugConnection(string $remoteSocket): void
    {
        if ($this->config['debug']) {
            $message = "|| *  {$remoteSocket}\r\n|| \r\n";
            if (null !== $this->config['debug_file']) {
                try {
                    file_put_contents($this->config['debug_file'], $message, \FILE_APPEND);
                } catch (Throwable $e) {}
            } else {
                echo $message;
            }
        }
    }

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @return void
     */
    protected function debugRequest(RequestInterface $request): void
    {
        if ($this->config['debug']) {
            $requestParts = explode("\r\n\r\n", (string)$request, 2);

            $message = '';

            foreach (explode("\r\n", $requestParts[0]) as $line) {
                $message .= "|| -> {$line}\r\n";
            }

            $message .= "|| \r\n";

            if (null !== $this->config['debug_file']) {
                try {
                    file_put_contents($this->config['debug_file'], $message, \FILE_APPEND);
                } catch (Throwable $e) {}
            } else {
                echo $message;
            }
        }
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return void
     */
    protected function debugResponse(ResponseInterface $response): void
    {
        if ($this->config['debug']) {
            $responseParts = explode("\r\n\r\n", (string)$response, 2);

            $message = '';

            foreach (explode("\r\n", $responseParts[0]) as $line) {
                $message .= "|| <- {$line}\r\n";
            }

            $message .= "|| <- \r\n\r\n";

            if (null !== $this->config['debug_file']) {
                try {
                    file_put_contents($this->config['debug_file'], $message, \FILE_APPEND);
                } catch (Throwable $e) {}
            } else {
                echo $message;
            }
        }
    }

    /**
     * @param string $string
     * @return string
     */
    protected function unchunkString(string $string): string
    {
        $result = '';

        while ('' !== $string) {
            $crlfPos = strpos($string, "\r\n");
            if (false === $crlfPos) {
                return $string;
            }

            $size = substr($string, 0, $crlfPos);

            $extPos = strpos($size, ';');
            if (false !== $extPos) {
                $size = substr($size, 0, $extPos);
            }

            if ('' === $size) {
                return $string;
            }

            $size = hexdec($size);
            if (0 === $size) {
                break;
            }

            $result .= substr($string, $crlfPos + 2, $size);
            $string = substr($string, $crlfPos + $size + 4);
        }

        return $result;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function ungzipString(string $string): string
    {
        return (extension_loaded('zlib') && false !== $result = gzdecode($string)) ? $result : $string;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function undeflateString(string $string): string
    {
        return (extension_loaded('zlib') && false !== $result = gzinflate($string)) ? $result : $string;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function uncompressString(string $string): string
    {
        return (extension_loaded('zlib') && false !== $result = gzuncompress($string)) ? $result : $string;
    }
}

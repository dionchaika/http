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
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The HTTP server request model.
 *
 * @see https://www.php-fig.org/psr/psr-7/
 * @see https://tools.ietf.org/html/rfc7230
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * The array of server parameters.
     *
     * @var mixed[]
     */
    protected $serverParams = [];

    /**
     * The array of request cookie parameters.
     *
     * @var mixed[]
     */
    protected $cookieParams = [];

    /**
     * The array of request query parameters.
     *
     * @var mixed[]
     */
    protected $queryParams = [];

    /**
     * The array of uploaded files.
     *
     * @var \Psr\Http\Message\UploadedFileInterface[]
     */
    protected $uploadedFiles = [];

    /**
     * The request parsed body.
     *
     * @var mixed[]|object|null
     */
    protected $parsedBody;

    /**
     * The array of request attributes.
     *
     * @var mixed[]
     */
    protected $attributes = [];

    /**
     * @param string                                     $method
     * @param \Psr\Http\Message\UriInterface|string|null $uri
     * @param mixed[]                                    $serverParams
     * @throws \InvalidArgumentException
     */
    public function __construct($method = 'GET', $uri = null, $serverParams = [])
    {
        $this->serverParams = $serverParams;
        parent::__construct($method, $uri);
    }

    /**
     * Create a new request from globals.
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public static function createFromGlobals()
    {
        $method = isset($_POST['_method'])
            ? $_POST['_method']
            : (!empty($_SERVER['REQUEST_METHOD'])
                ? $_SERVER['REQUEST_METHOD']
                : 'GET'
            );

        $protocolVersion = '1.1';
        if (!empty($_SERVER['SERVER_PROTOCOL'])) {
            $serverProtocolParts = explode('/', $_SERVER['SERVER_PROTOCOL'], 2);
            if (!empty($serverProtocolParts[1])) {
                $protocolVersion = $serverProtocolParts[1];
            }
        }

        $uri = Uri::createFromGlobals();
        $uploadedFiles = UploadedFile::createFromGlobals();

        $request = (new static($method, $uri, $_SERVER))
            ->withoutHeader('Host')
            ->withProtocolVersion($protocolVersion)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withCookieParams($_COOKIE)
            ->withUploadedFiles($uploadedFiles);

        foreach ($_SERVER as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headerNameParts = array_map('ucfirst', explode('-', $headerName));

                $headerName = implode('-', $headerNameParts);
                $headerValues = array_map('trim', explode(',', $value));

                $request = $request->withAddedHeader($headerName, $headerValues);
            }
        }

        return $request->withBody(new Stream('php://input'));
    }

    /**
     * Get the array of server parameters.
     *
     * @return mixed[]
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * Get the array of request cookie parameters.
     *
     * @return mixed[]
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * Return an instance with
     * the specified request cookie parameters.
     *
     * @param mixed[] $cookies
     * @return self
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * Get the array of request query parameters.
     *
     * @return mixed[]
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Return an instance with
     * the specified request query parameters.
     *
     * @param mixed[] $query
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * Get the array of uploaded files.
     *
     * @return \Psr\Http\Message\UploadedFileInterface[]
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Return an instance
     * with the specified uploaded files.
     *
     * @param \Psr\Http\Message\UploadedFileInterface[] $uploadedFiles
     * @return self
     * @throws \InvalidArgumentException
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $new = clone $this;
        $new->uploadedFiles = $new->filterUploadedFiles($uploadedFiles);

        return $new;
    }

    /**
     * Get the request parsed body.
     *
     * @return array|object|null
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * Return an instance
     * with the specified request parsed body.
     *
     * @param mixed[]|object|null $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->parsedBody = $new->filterParsedBody($data);

        return $new;
    }

    /**
     * Get the array of request attributes.
     *
     * @return mixed[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get the request attribute.
     *
     * @param string     $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
    }

    /**
     * Return an instance
     * with the specified request attribute.
     *
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function withAttribute($name, $value)
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /**
     * Return an instance
     * without the specified request attribute.
     *
     * @param string $name
     * @return self
     */
    public function withoutAttribute($name)
    {
        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * Filter an array of uploaded files.
     *
     * @param \Psr\Http\Message\UploadedFileInterface[] $uploadedFiles
     * @return \Psr\Http\Message\UploadedFileInterface[]
     * @throws \InvalidArgumentException
     */
    protected function filterUploadedFiles(array $uploadedFiles)
    {
        foreach ($uploadedFiles as $uploadedFile) {
            if (is_array($uploadedFile)) {
                $this->filterUploadedFiles($uploadedFile);
            } else if (!$uploadedFile instanceof UploadedFileInterface) {
                throw new InvalidArgumentException(
                    'Invalid structure of uploaded files tree!'
                );
            }
        }

        return $uploadedFiles;
    }

    /**
     * Filter a request parsed body.
     *
     * @param array|object|null $data
     * @return array|object|null
     * @throws \InvalidArgumentException
     */
    protected function filterParsedBody($data)
    {
        if (null !== $data && !is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException(
                'Invalid parsed body! Parsed body must be an array or an object.'
            );
        }

        return $data;
    }
}

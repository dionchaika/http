<?php

/**
 * The PSR HTTP Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\Factory\Traits;

use Dionchaika\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

trait ServerRequestFactoryTrait
{
    /**
     * Create a new server request.
     *
     * @param string                                $method
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param mixed[]                               $serverParams
     * @return \Psr\Http\Message\ServerRequestInterface
     * @throws \InvalidArgumentException
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, $serverParams);
    }
}

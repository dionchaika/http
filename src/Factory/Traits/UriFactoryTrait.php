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

use Dionchaika\Http\Uri;
use Psr\Http\Message\UriInterface;

trait UriFactoryTrait
{
    /**
     * Create a new URI.
     *
     * @param string $uri
     * @return \Psr\Http\Message\UriInterface
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}

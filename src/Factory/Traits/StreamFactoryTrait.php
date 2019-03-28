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

use Dionchaika\Http\Stream;
use Psr\Http\Message\StreamInterface;

trait StreamFactoryTrait
{
    /**
     * Create a new stream from string.
     *
     * @param string $content
     * @return \Psr\Http\Message\StreamInterface
     * @throws \RuntimeException
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    /**
     * Create a new stream from file.
     *
     * @param string $filename
     * @param string $mode
     * @return \Psr\Http\Message\StreamInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new Stream($filename, $mode);
    }

    /**
     * Create a new stream from resource.
     *
     * @param resource $resource
     * @return \Psr\Http\Message\StreamInterface
     * @throws \InvalidArgumentException
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}

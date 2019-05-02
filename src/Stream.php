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

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * The PSR-7 stream wrapper.
 *
 * @see https://www.php-fig.org/psr/psr-7/
 */
class Stream implements StreamInterface
{
    /**
     * The writable stream modes.
     */
    const WRITABLE_STREAM_MODES = [

        'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'

    ];

    /**
     * The readable stream modes.
     */
    const READABLE_STREAM_MODES = [

        'r', 'r+', 'w+', 'a+', 'x+', 'c+'

    ];

    /**
     * The stream underlying resource.
     *
     * @var resource
     */
    protected $resource;

    /**
     * The stream size.
     *
     * @var int|null
     */
    protected $size;

    /**
     * Is the stream seekable.
     *
     * @var bool
     */
    protected $seekable = false;

    /**
     * Is the stream writable.
     *
     * @var bool
     */
    protected $writable = false;

    /**
     * Is the stream readable.
     *
     * @var bool
     */
    protected $readable = false;

    /**
     * @param string|resource $contentOrFilenameOrResource
     * @param string          $mode
     * @param mixed[]         $opts
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct($contentOrFilenameOrResource = '', $mode = 'r', $opts = [])
    {
        if (is_string($contentOrFilenameOrResource)) {
            if (
                @is_file($contentOrFilenameOrResource) ||
                0 === strpos($contentOrFilenameOrResource, 'php://')
            ) {
                $mode = $this->filterMode($mode);

                $resource = fopen($contentOrFilenameOrResource, $mode);
                if (false === $resource) {
                    throw new RuntimeException(
                        'Unable to create a stream from file: '.$contentOrFilenameOrResource.'!'
                    );
                }

                $this->resource = $resource;
            } else {
                $resource = fopen('php://temp', 'r+');
                if (false === $resource || false === fwrite($resource, $contentOrFilenameOrResource)) {
                    throw new RuntimeException(
                        'Unable to create a stream from string!'
                    );
                }

                $this->resource = $resource;
            }
        } else if (is_resource($contentOrFilenameOrResource)) {
            $this->resource = $contentOrFilenameOrResource;
        } else {
            throw new InvalidArgumentException(
                'Resource must be the PHP resource!'
            );
        }

        if (isset($opts['size']) && is_int($opts['size']) && 0 <= $opts['size']) {
            $this->size = $opts['size'];
        } else {
            $fstat = fstat($this->resource);
            if (false === $fstat) {
                $this->size = null;
            } else {
                $this->size = !empty($fstat['size']) ? $fstat['size'] : null;
            }
        }

        $meta = stream_get_meta_data($this->resource);

        $this->seekable = (isset($opts['seekable']) && is_bool($opts['seekable']))
            ? $opts['seekable']
            : (!empty($meta['seekable']) ? $meta['seekable'] : false);

        if (isset($opts['writable']) && is_bool($opts['writable'])) {
            $this->writable = $opts['writable'];
        } else {
            foreach (static::WRITABLE_STREAM_MODES as $mode) {
                if (0 === strncmp($meta['mode'], $mode, strlen($mode))) {
                    $this->writable = true;
                    break;
                }
            }
        }

        if (isset($opts['readable']) && is_bool($opts['readable'])) {
            $this->readable = $opts['readable'];
        } else {
            foreach (static::READABLE_STREAM_MODES as $mode) {
                if (0 === strncmp($meta['mode'], $mode, strlen($mode))) {
                    $this->readable = true;
                    break;
                }
            }
        }
    }

    /**
     *  Close the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if (null !== $this->resource && fclose($this->resource)) {
            $this->detach();
        }
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * @return resource|null
     */
    public function detach()
    {
        $resource = $this->resource;

        if (null !== $resource) {
            $this->resource = $this->size = null;
            $this->seekable = $this->writable = $this->readable = false;
        }

        return $resource;
    }

    /**
     * Get the stream size.
     *
     * @return int|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Return the current position of the stream read/write pointer.
     *
     * @return int
     * @throws \RuntimeException
     */
    public function tell()
    {
        if (null === $this->resource) {
            throw new RuntimeException(
                'Stream resource is detached!'
            );
        }

        $position = ftell($this->resource);
        if (false === $position) {
            throw new RuntimeException(
                'Unable to tell the current position of the stream read/write pointer!'
            );
        }

        return $position;
    }

    /**
     * Check is the stream at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return null === $this->resource || feof($this->resource);
    }

    /**
     * Check is the stream seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * @param int $offset
     * @param int $whence
     * @return void
     * @throws \RuntimeException
     */
    public function seek($offset, $whence = \SEEK_SET)
    {
        if (null === $this->resource) {
            throw new RuntimeException(
                'Stream resource is detached!'
            );
        }

        if (!$this->seekable) {
            throw new RuntimeException(
                'Stream is not seekable!'
            );
        }

        if (-1 === fseek($this->resource, $offset, $whence)) {
            throw new RuntimeException(
                'Unable to seek to a position in the stream!'
            );
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Check is the stream writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string
     * @return int
     * @throws \RuntimeException
     */
    public function write($string)
    {
        if (null === $this->resource) {
            throw new RuntimeException(
                'Stream resource is detached!'
            );
        }

        if (!$this->writable) {
            throw new RuntimeException(
                'Stream is not writable!'
            );
        }

        $bytes = fwrite($this->resource, $string);
        if (false === $bytes) {
            throw new RuntimeException(
                'Unable to write data to the stream!'
            );
        }

        $fstat = fstat($this->resource);
        if (false === $fstat) {
            $this->size = null;
        } else {
            $this->size = !empty($fstat['size']) ? $fstat['size'] : null;
        }

        return $bytes;
    }

    /**
     * Check is the stream readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length
     * @return string
     * @throws \RuntimeException
     */
    public function read($length)
    {
        if (null === $this->resource) {
            throw new RuntimeException(
                'Stream resource is detached!'
            );
        }

        if (!$this->readable) {
            throw new RuntimeException(
                'Stream is not readable!'
            );
        }

        $data = fread($this->resource, $length);
        if (false === $data) {
            throw new RuntimeException(
                'Unable to read data from the stream!'
            );
        }

        return $data;
    }

    /**
     * Get the contents of the stream.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getContents()
    {
        if (null === $this->resource) {
            throw new RuntimeException(
                'Stream resource is detached!'
            );
        }

        if (!$this->readable) {
            throw new RuntimeException(
                'Stream is not readable!'
            );
        }

        $contents = stream_get_contents($this->resource);
        if (false === $contents) {
            throw new RuntimeException(
                'Unable to get the contents of the stream!'
            );
        }

        return $contents;
    }

    /**
     * Get the stream metadata
     * as an associative array or retrieve a specific key.
     *
     * @param string|null $key
     * @return mixed|mixed[]|null
     */
    public function getMetadata($key = null)
    {
        $meta = stream_get_meta_data($this->resource);

        if (null === $key) {
            return $meta;
        }

        return !empty($meta[$key]) ? $meta[$key] : null;
    }

    /**
     * Filter a stream mode.
     *
     * @param string $mode
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterMode($mode) {
        if (!is_string($mode)) {
            throw new InvalidArgumentException(
                'Invalid mode! Mode must be a string.'
            );
        }

        if (!in_array($mode, static::WRITABLE_STREAM_MODES) && !in_array($mode, static::READABLE_STREAM_MODES)) {
            $validModes = [];

            foreach (static::WRITABLE_STREAM_MODES as $mode) {
                if (!in_array($mode, $validModes)) {
                    $validModes[] = $mode;
                }
            }

            foreach (static::READABLE_STREAM_MODES as $mode) {
                if (!in_array($mode, $validModes)) {
                    $validModes[] = $mode;
                }
            }

            $validModes = array_map(function ($validMode) {
                return '"'.$validMode.'"';
            }, $validModes);

            $validModes = implode(', ', $validModes);

            throw new InvalidArgumentException(
                'Invalid mode! Valid modes: '.$validModes
            );
        }

        return $mode;
    }

    /**
     * Read all data from the stream
     * into a string, from the beginning to end.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            if ($this->seekable) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (Exception $e) {
            return '';
        }
    }
}

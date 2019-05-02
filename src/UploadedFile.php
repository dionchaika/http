<?php

/**
 * The PSR HTTP Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http;

use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * The PSR-7 uploaded file wrapper.
 *
 * @see https://www.php-fig.org/psr/psr-7/
 */
class UploadedFile implements UploadedFileInterface
{
    /**
     * The uploaded file name.
     *
     * @var string
     */
    protected $filename;

    /**
     * Is the uploaded file moved.
     *
     * @var bool
     */
    protected $moved = false;

    /**
     * The uploaded file stream.
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $stream;

    /**
     * The uploaded file size.
     *
     * @var int|null
     */
    protected $size;

    /**
     * The uploaded file error.
     *
     * @var int
     */
    protected $error = \UPLOAD_ERR_OK;

    /**
     * The uploaded file client name.
     *
     * @var string|null
     */
    protected $clientFilename;

    /**
     * The uploaded file client media type.
     *
     * @var string|null
     */
    protected $clientMediaType;

    /**
     * @param \Psr\Http\Message\StreamInterface|string $filenameOrStream
     * @param int|null                                 $size
     * @param int                                      $error
     * @param string|null                              $clientFilename
     * @param string|null                              $clientMediaType
     * @throws \InvalidArgumentException
     */
    public function __construct(
        $filenameOrStream,
        $size = null,
        $error = \UPLOAD_ERR_OK,
        $clientFilename = null,
        $clientMediaType = null
    ) {
        if ($filenameOrStream instanceof StreamInterface) {
            if (!$filenameOrStream->isReadable()) {
                throw new InvalidArgumentException(
                    'Invalid stream! Stream is not readable.'
                );
            }

            $this->stream = $filenameOrStream;
            $this->size = $size ?? $filenameOrStream->getSize();
        } else {
            $this->filename = $filenameOrStream;
            $this->size = $size;
        }

        $this->error = $this->filterError($error);
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * Create a new array of uploaded files from globals.
     *
     * @return mixed[]
     * @throws \InvalidArgumentException
     */
    public static function createFromGlobals()
    {
        return static::normalizeFiles($_FILES);
    }

    /**
     * Normalize an array of uploaded files.
     *
     * @param mixed[] $files
     * @return mixed[]
     * @throws \InvalidArgumentException
     */
    public static function normalizeFiles($files)
    {
        $normalized = [];

        foreach ($files as $name => $info) {
            if ($info instanceof UploadedFileInterface) {
                $normalized[$name] = $info;
                continue;
            }

            if (!isset($info['error'])) {
                if (is_array($info)) {
                    $normalized[$name] = static::normalizeFiles($info);
                }

                continue;
            }

            $normalized[$name] = [];

            if (!is_array($info['error'])) {
                $normalized[$name] = new static(
                    $info['tmp_name'],
                    !empty($info['size']) ? $info['size'] : null,
                    $info['error'],
                    !empty($info['name']) ? $info['name'] : null,
                    !empty($info['type']) ? $info['type'] : null
                );
            } else {
                $nestedInfo = [];
                foreach (array_keys($info['error']) as $key) {
                    $nestedInfo[$key]['tmp_name'] = $info['tmp_name'][$key];
                    $nestedInfo[$key]['size'] = $info['size'][$key];
                    $nestedInfo[$key]['error'] = $info['error'][$key];
                    $nestedInfo[$key]['name'] = $info['name'][$key];
                    $nestedInfo[$key]['type'] = $info['type'][$key];

                    $normalized[$name] = static::normalizeFiles($nestedInfo);
                }
            }
        }

        return $normalized;
    }

    /**
     * Get the uploaded file stream.
     *
     * @return \Psr\Http\Message\StreamInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new RuntimeException(
                'Stream is not avaliable! Uploaded file is moved.'
            );
        }

        if (null === $this->stream) {
            $this->stream = new Stream($this->filename);
        }

        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @param string $targetPath
     * @return void
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new RuntimeException(
                'Uploaded file is already moved!'
            );
        }

        $targetPath = $this->filterTargetPath($targetPath);

        if (null !== $this->filename) {
            if ('cli' === \PHP_SAPI) {
                if (false === rename($this->filename, $targetPath)) {
                    throw new RuntimeException(
                        'Unable to rename the uploaded file!'
                    );
                }
            } else {
                if (false === is_uploaded_file($this->filename) || false === move_uploaded_file($this->filename, $targetPath)) {
                    throw new RuntimeException(
                        'Unable to move the uploaded file!'
                    );
                }
            }
        } else {
            $oldStream = $this->getStream();
            $newStream = new Stream($targetPath);

            if (false === stream_copy_to_stream($oldStream, $newStream)) {
                throw new RuntimeException(
                    'Unable to copy the uploaded file stream!'
                );
            }
        }

        $this->moved = true;
    }

    /**
     * Get the uploaded file size.
     *
     * @return int|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get the uploaded file error.
     *
     * @return int
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get the uploaded file client name.
     *
     * @return string|null
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * Get the uploaded file client media type.
     *
     * @return string|null
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * Filter an uploaded file error.
     *
     * @param int $error
     * @return int
     */
    protected function filterError($error)
    {
        if (!is_int($error)) {
            throw new InvalidArgumentException(
                'Invalid error! Error must be an integer.'
            );
        }

        if (
            \UPLOAD_ERR_OK !== $error &&
            \UPLOAD_ERR_INI_SIZE !== $error &&
            \UPLOAD_ERR_FORM_SIZE !== $error &&
            \UPLOAD_ERR_PARTIAL !== $error &&
            \UPLOAD_ERR_NO_FILE !== $error &&
            \UPLOAD_ERR_NO_TMP_DIR !== $error &&
            \UPLOAD_ERR_CANT_WRITE !== $error &&
            \UPLOAD_ERR_EXTENSION !== $error
        ) {
            throw new InvalidArgumentException(
                'Invalid error! Error must be a PHP file upload error.'
            );
        }

        return $error;
    }

    /**
     * Filter an uploaded file target path.
     *
     * @param string $targetPath
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterTargetPath($targetPath)
    {
        if (!is_string($targetPath)) {
            throw new InvalidArgumentException(
                'Invalid target path! Target path must be a string.'
            );
        }

        if ('' === $targetPath) {
            throw new InvalidArgumentException(
                'Invalid target path! Target path can not be empty.'
            );
        }

        return $targetPath;
    }
}

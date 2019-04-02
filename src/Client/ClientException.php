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
use Throwable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Client\ClientExceptionInterface;

class ClientException extends Exception implements ClientExceptionInterface
{
    /**
     * The client exception request.
     *
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     */
    public function __construct(
        RequestInterface $request,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->request = $request;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the client exception request.
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}

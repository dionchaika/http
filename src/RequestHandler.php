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

use Closure;
use RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * The array of
     * request handler middleware.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * The fallback handler
     * instance of the request handler.
     *
     * @var \Psr\Http\Server\RequestHandlerInterface
     */
    protected $fallbackHandler;

    /**
     * @param \Psr\Http\Server\RequestHandlerInterface $fallbackHandler
     * @param array $middleware
     */
    public function __construct(
        RequestHandlerInterface $fallbackHandler,
        array $middleware = []
    ) {
        $this->middleware = $middleware;
        $this->fallbackHandler = $fallbackHandler;
    }

    /**
     * Add a new middleware.
     *
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|string $middleware
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function add($middleware): RequestHandlerInterface
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Handle a request a return a response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \RuntimeException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (0 === count($this->middleware)) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = array_shift($this->middleware);

        if ($middleware instanceof Closure) {
            return $middleware($request, $this);
        } else if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this);
        } else if (is_string($middleware) && class_exists($middleware)) {
            $middleware = new $middleware;
            if (method_exists($middleware, ['process'])) {
                return $middleware->process($request, $this);
            }
        }

        throw new RuntimeException(
            'Invalid middleware!'
            .' Middleware must be an instance of \\Closure'
            .' or an instance of \\Psr\\Http\\Server\\MiddlewareInterface'
        );
    }
}

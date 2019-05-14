<?php

/**
 * The PSR HTTP Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\Server;

use Closure;
use RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The PSR-15 request handler model.
 *
 * @see https://www.php-fig.org/psr/psr-15/
 */
class RequestHandler implements RequestHandlerInterface
{
    /**
     * The array of
     * request handler middleware.
     *
     * @var mixed[]
     */
    protected $middleware = [];

    /**
     * The fallback request handler.
     *
     * @var \Psr\Http\Server\RequestHandlerInterface|\Closure|string
     */
    protected $fallbackHandler;

    /**
     * @param \Psr\Http\Server\RequestHandlerInterface|\Closure|string $fallbackHandler
     * @param mixed[]                                                  $middleware
     */
    public function __construct($fallbackHandler, array $middleware = []) {
        $this->fallbackHandler = $fallbackHandler;
        $this->middleware = $middleware;
    }

    /**
     * Add a new middleware.
     *
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|string $middleware
     * @return self
     */
    public function add($middleware): self
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
            $fallbackHandler = $this->fallbackHandler;

            if ($fallbackHandler instanceof Closure) {
                return $fallbackHandler($request);
            } else if ($fallbackHandler instanceof RequestHandlerInterface) {
                return $fallbackHandler->handle($request);
            } else if (is_string($fallbackHandler) && class_exists($fallbackHandler)) {
                $fallbackHandler = new $fallbackHandler;
                if (method_exists($fallbackHandler, ['handle'])) {
                    return $fallbackHandler->handle($request);
                }
            }

            throw new RuntimeException(
                'Invalid fallback handler! '
                .'Fallback handler must be an instance of \\Closure '
                .'or an instance of \\Psr\\Http\\Server\\RequestHandlerInterface.'
            );
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
            'Invalid middleware! '
            .'Middleware must be an instance of \\Closure '
            .'or an instance of \\Psr\\Http\\Server\\MiddlewareInterface.'
        );
    }
}

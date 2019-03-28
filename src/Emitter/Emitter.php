<?php

/**
 * The Psr Http Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\Emitter;

use RuntimeException;
use Psr\Http\Message\ResponseInterface;

class Emitter
{
    /**
     * Emit a response to the client.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return void
     * @throws \RuntimeException
     */
    public static function emit(ResponseInterface $response): void
    {
        if (headers_sent()) {
            throw new RuntimeException(
                'Unable to emit the response! Headers are already sent.'
            );
        }

        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        $protocolVersion = $response->getProtocolVersion();
        $protocolVersion = ('' === $protocolVersion) ? '1.1' : $protocolVersion;

        header('HTTP/'.$protocolVersion.' '.$statusCode.' '.$reasonPhrase);

        if ($response->hasHeader('Set-Cookie')) {
            foreach ($response->getHeader('Set-Cookie') as $setCookie) {
                header('Set-Cookie: '.$setCookie, false);
            }

            $response = $response->withoutHeader('Set-Cookie');
        }

        foreach (array_keys($response->getHeaders()) as $name) {
            header($name.': '.$response->getHeaderLine($name));
        }

        echo $response->getBody();
        exit;
    }
}

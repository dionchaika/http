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
     * Emit a response to browser.
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

        header(
            'HTTP/'
            .$response->getProtocolVersion()
            .' '
            .$response->getStatusCode()
            .' '
            .$response->getReasonPhrase(),
            true
        );

        foreach (array_keys($response->getHeaders()) as $header) {
            if ('set-cookie' === strtolower($header)) {
                foreach ($response->getHeader('Set-Cookie') as $setCookie) {
                    header($header.': '.$setCookie, false);
                }
            } else {
                header($header.': '.$response->getHeaderLine($header), true);
            }
        }

        fwrite(fopen('php://output', 'w'), $response->getBody());

        exit;
    }
}

<?php

/**
 * The PSR HTTP Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\Factory;

use Psr\Http\Message\ResponseFactoryInterface;
use Dionchaika\Http\Factory\Traits\ResponseFactoryTrait;

class ResponseFactory implements ResponseFactoryInterface
{
    use ResponseFactoryTrait;
}

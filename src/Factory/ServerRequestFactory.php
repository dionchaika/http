<?php

/**
 * The Psr Http Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\Factory;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Dionchaika\Http\Factory\Traits\ServerRequestFactoryTrait;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    use ServerRequestFactoryTrait;
}

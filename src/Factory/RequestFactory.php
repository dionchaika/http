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

use Psr\Http\Message\RequestFactoryInterface;
use Dionchaika\Http\Factory\Traits\RequestFactoryTrait;

class RequestFactory implements RequestFactoryInterface
{
    use RequestFactoryTrait;
}

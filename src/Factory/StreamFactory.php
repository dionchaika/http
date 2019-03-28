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

use Psr\Http\Message\StreamFactoryInterface;
use Dionchaika\Http\Factory\Traits\StreamFactoryTrait;

class StreamFactory implements StreamFactoryInterface
{
    use StreamFactoryTrait;
}

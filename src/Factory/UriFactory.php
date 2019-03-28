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

use Psr\Http\Message\UriFactoryInterface;
use Dionchaika\Http\Factory\Traits\UriFactoryTrait;

class UriFactory implements UriFactoryInterface
{
    use UriFactoryTrait;
}

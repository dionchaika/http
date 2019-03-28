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
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Dionchaika\Http\Factory\Traits\UriFactoryTrait;
use Dionchaika\Http\Factory\Traits\StreamFactoryTrait;
use Dionchaika\Http\Factory\Traits\RequestFactoryTrait;
use Dionchaika\Http\Factory\Traits\ResponseFactoryTrait;
use Dionchaika\Http\Factory\Traits\UploadedFileFactoryTrait;
use Dionchaika\Http\Factory\Traits\ServerRequestFactoryTrait;

class HttpFactory implements
    UriFactoryInterface,
    StreamFactoryInterface,
    RequestFactoryInterface,
    ResponseFactoryInterface,
    UploadedFileFactoryInterface,
    ServerRequestFactoryInterface
{
    use
        UriFactoryTrait,
        StreamFactoryTrait,
        RequestFactoryTrait,
        ResponseFactoryTrait,
        UploadedFileFactoryTrait,
        ServerRequestFactoryTrait;
}

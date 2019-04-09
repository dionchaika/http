# Http
The Psr Http Library

## Requirements
1. PHP 7
2. PHP Zlib extention (optional)

## Installation
```bash
composer require dionchaika/http:dev-master
```

```php
<?php

require_once 'vendor/autoload.php';
```

## HTTP-message classes
```php
<?php

use Dionchaika\Http\Uri;
use Dionchaika\Http\Stream;
use Dionchaika\Http\Request;
use Dionchaika\Http\Response;
use Dionchaika\Http\UploadedFile;
use Dionchaika\Http\ServerRequest;

/* URI usage example */
$uri = (new Uri('http://example.com/'))
    ->withQuery('foo=bar&baz=bat');


/* Stream usage example */
$stream = new Stream('Hello, World!');
echo $stream;

/* Request usage example */
$request = new Request('GET', 'http://example.com/')
    ->withAddedHeader('Cookie', 'foo=bar')
    ->withAddedHeader('Cookie', 'baz=bat');

/* Response usage example */
$response = new Response(200, 'OK')
    ->withAddedHeader('Set-Cookie', 'foo=bar; Max-Age=3600; Path=/; Secure; HttpOnly')
    ->withAddedHeader('Set-Cookie', 'baz=bat; Max-Age=3600; Path=/; Secure; HttpOnly');
$response->getBody()->write('Hello, World!');
```

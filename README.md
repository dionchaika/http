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

## Basic usage
For client side requests:

```php
<?php

use Dionchaika\Http\Client\Client;
use Dionchaika\Http\Factory\HttpFactory;

$client = new Client([
    'redirects' => true,
    'max_redirects' => 10
]);

$httpFactory = new HttpFactory;

$response = $clinet->sendRequest(
    $httpFactory->createRequest('GET', 'https://github.com/');
);

if (200 === $response->getStatusCode()) {
    // [some code]
}

// [some code]
```

For server side requests:

```php
<?php

use Dionchaika\Http\ServerRequest;
use Dionchaika\Http\RequestHandler;
use Dionchaika\Http\Emitter\Emitter;

use App\Handler\NotFoundHandler;
use App\Middleware\CsrfMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoutingMiddleware;

$handler = (new RequestHandler(new NotFoundHandler))
    ->add(new CsrfMiddleware)
    ->add(new AuthMiddleware)
    ->add(new RoutingMiddleware);

$response = $handler->handle(
    ServerRequest::createFromGlobals()
);

Emitter::emit($response);

```

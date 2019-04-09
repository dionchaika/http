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

## Message classes

### 1. Uri
```php
<?php

use Dionchaika\Http\Uri;

$uri = new Uri('http://example.com/index.php?foo=bar&baz=bat');

$scheme = $uri->getScheme(); /* [http] */
$host = $uri->getHost(); /* [example.com] */
$path = $uri->getPath(); /* [/index.php] */
$query = $uri->getQuery(); /* [foo=bar&baz=bat] */

$uri = (new Uri)
    ->withScheme('http')
    ->withHost('example.com')
    ->withPath('/index.php')
    ->withQuery('foo=bar&baz=bat');

echo $uri; /* [http://example.com/index.php?foo=bar&baz=bat] */
```

You can also create a new URI instance from PHP globals:

```php
<?php

use Dionchaika\Http\Uri;

$uri = Uri::createFromGlobals();
```

### 2. Request
```php
<?php

use Dionchaika\Http\Request;

$request = (new Request('GET', 'http://example.com/index.php?foo=bar&baz=bat'))
    ->withHeader('Cookie', ['foo=bar', 'baz=bat'])
    ->withHeader('Accept', 'text/html; charset=utf-8');

echo $request; /* [GET /index.php?foo=bar&baz=bat HTTP/1.1\r\nHost: example.com\r\n...] */
```

You can also create a new request instance from string:

```php
<?php

use Dionchaika\Http\Request;

$message = "GET /index.php?foo=bar&baz=bat HTTP/1.1\r\n";
$message .= "Host: example.com\r\n";
$message .= "Cookie: foo=bar; baz=bat\r\n";
$message .= "Accept: text/html; charset=utf-8\r\n";
$message .= "\r\n";

$request = Request::createFromString($message);
```

### 2. Response
```php
<?php

use Dionchaika\Http\Response;

$response = (new Response(200, 'OK'))
    ->withHeader('Contet-Type', 'text/html; charset=utf-8')
    ->withAddedHeader('Set-Cookie', 'foo=bar; Max-Age=3600; Path=/; Secure; HttpOnly')
    ->withAddedHeader('Set-Cookie', 'baz=bat; Max-Age=3600; Path=/; Secure; HttpOnly');

$response->getBody()->write('<!DOCTYPE html><html>...</html>');
$response = $response->withHeader('Content-Length', (string)$response->getBody()->getSize());

echo $response; /* [HTTP/1.1 200 OK\r\nContet-Type: text/html; charset=utf-8...] */
```

You can also create a new response instance from string:

```php
<?php

use Dionchaika\Http\Request;

$message = "HTTP/1.1 200 OK\r\n";
$message .= "Contet-Type: text/html; charset=utf-8\r\n";
$message .= "Set-Cookie: foo=bar; Max-Age=3600; Path=/; Secure; HttpOnly\r\n";
$message .= "Set-Cookie: baz=bat; Max-Age=3600; Path=/; Secure; HttpOnly\r\n";
$message .= "\r\n";
$message .= "<!DOCTYPE html><html>...</html>";

$response = Response::createFromString($message);
```

### 3. Stream
```php
<?php

use Dionchaika\Http\Stream;

/* Create a new stream from string */
$stream = new Stream('Hello, World!');

/* Create a new stream from file */
$stream = new Stream('/path/to/file');

/* Create a new stream from resorce */
$stream = new Stream(fopen('php://input', 'r'));

$size = $stream->getSize(); /* Returns the stream size in bytes */
$isReadable = $stream->isReadable(); /* Returns true if the stream is readable */
$isWritable = $stream->isWritable(); /* Returns true if the stream is writable */

$stream->write(' This is some text...');

echo $stream; /* [Hello, World! This is some text...] */
```

You can also pass optional parameters to the stream:

```php
<?php

use Dionchaika\Http\Stream;

$stream = new Stream('This is some text...', [
    'size' => 1024,
    'seekable' => true,
    'readable' => true,
    'writable' => false
]);
```

### 4. ServerRequest
```php
<?php

use Dionchaika\Http\ServerRequest;

$request = (new ServerRequest('GET', 'http://example.com/index.php?foo=bar&baz=bat', $_SERVER))
    ->withAttribute('action', 'index')
    ->withAttribute('controller', 'home');

$user = $request->getQueryParams()['user']; /* Returns $_GET['user'] if set */
$password = $request->getQueryParams()['password']; /* Returns $_GET['password'] if set */
```

You can also create a new server request instance from PHP globals:

```php
<?php

use Dionchaika\Http\ServerRequest;

$request = ServerRequest::createFromGlobals();

/* ... */

switch ($request->getUri()->getPath()) {
    case '/':
        /* ... */
        break;
    case '/about':
        /* ... */
        break;
    case '/contact':
        /* ... */
        break;
}

/* ... */
```

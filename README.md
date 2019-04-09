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

$scheme = $uri->getScheme(); // (http)
$host = $uri->getHost(); // (example.com)
$path = $uri->getPath(); // (/index.php)
$query = $uri->getQuery(); // (foo=bar&baz=bat)

$uri = (new Uri)
    ->withScheme('http')
    ->withHost('example.com')
    ->withPath('/index.php')
    ->withQuery('foo=bar&baz=bat');
```

You can also create a new URI instance from PHP globals:

```php
<?php

use Dionchaika\Http\Uri;

$uri = Uri::createFromGlobals();
```

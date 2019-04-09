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

$uri = new Uri('http://user:password@example.com:8080/index.php?foo=bar&baz=bat#hash');

$scheme = $uri->getScheme(); /* http */
$authority = $uri->getAuthority(); /* user:password@example.com:8080 */
$userInfo = $uri->getUserInfo(); /* user:password */
$host = $uri->getHost(); /* example.com */
$port = $uri->getPort(); /* 8080 */
$path = $uri->getPath(); /* /index.php */
$query = $uri->getQuery(); /* foo=bar&baz=bat */
$fragment = $uri->getFragment(); /* hash */

$uri = (new Uri)
    ->withScheme('http')
    ->withUserInfo('user:password')
    ->withHost('example.com')
    ->withPort(8080)
    ->withPath('/index.php')
    ->withQuery('foo=bar&baz=bat')
    ->withFragment('hash');
```

You can also create a URI instance from PHP globals:

```php
$uri = Uri::createFromGlobals();
```

# Alibaba Cloud API Gateway HTTP Request Signer for PHP

[![Latest version](https://img.shields.io/packagist/v/ion-bazan/aliyun-http-signer.svg)](https://packagist.org/packages/ion-bazan/aliyun-http-signer)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/IonBazan/aliyun-http-signer/Tests)](https://github.com/IonBazan/aliyun-http-signer/actions)
[![PHP version](https://img.shields.io/packagist/php-v/ion-bazan/aliyun-http-signer.svg)](https://packagist.org/packages/ion-bazan/aliyun-http-signer)
[![Codecov](https://img.shields.io/codecov/c/gh/IonBazan/aliyun-http-signer)](https://codecov.io/gh/IonBazan/aliyun-http-signer)
[![Downloads](https://img.shields.io/packagist/dt/ion-bazan/aliyun-http-signer.svg)](https://packagist.org/packages/ion-bazan/aliyun-http-signer)
[![License](https://img.shields.io/packagist/l/ion-bazan/aliyun-http-signer.svg)](https://packagist.org/packages/ion-bazan/aliyun-http-signer)

This library implements [Alibaba Cloud API Gateway request signature](https://www.alibabacloud.com/help/doc-detail/29475.htm) calculation for [PSR-7](https://www.php-fig.org/psr/psr-7/) compatible requests.
It integrates with [Guzzle](https://github.com/guzzle/guzzle) by providing a simple [Middleware](http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#middleware) but can be used with any PSR-7-compatible client.

# Installation
Use [Composer](https://getcomposer.org/) to install the package using:

```bash
composer require ion-bazan/aliyun-http-signer
```

# Usage

## Sign a PSR-7-compatible API request 

```php
<?php

require_once 'vendor/autoload.php';

use IonBazan\AliyunSigner\Key;
use IonBazan\AliyunSigner\RequestSigner;
use Psr\Http\Message\RequestInterface;

function signRequest(RequestInterface $request): RequestInterface
{
    // Provide credentials
    $appId = '12345678';
    $secret = base64_encode('secret');
    
    // Create signer
    $signer = new RequestSigner(new Key($appId, $secret));

    return $signer->signRequest($request);
}
```

## Sign an API request using Guzzle middleware

```php
<?php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use IonBazan\AliyunSigner\Key;
use IonBazan\AliyunSigner\RequestSigner;
use IonBazan\AliyunSigner\Guzzle\RequestSignerMiddleware;

// Provide credentials
$appId = '12345678';
$secret = base64_encode('secret');

// Create signer and middleware
$signer = new RequestSigner(new Key($appId, $secret));
$middleware = new RequestSignerMiddleware($signer);
$stack = HandlerStack::create();
$stack->push($middleware);

$client = new Client(['handler' => $stack]);
$response = $client->get('https://example.com/api/v1/test');
```

# Bugs & issues

If you found a bug or security vulnerability, please [open an issue](https://github.com/IonBazan/aliyun-http-signer/issues/new)

# Contributing

Please feel free to submit Pull Requests adding new features or fixing bugs.

Please note that code must follow PSR-1, PSR-2, PSR-4 and PSR-7.  

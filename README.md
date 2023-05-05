# Alibaba Cloud API Gateway HTTP Request Signer for PHP

[![Latest version](https://img.shields.io/packagist/v/ion-bazan/aliyun-http-signer.svg)](https://packagist.org/packages/ion-bazan/aliyun-http-signer)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/IonBazan/aliyun-http-signer/php.yml?branch=main)](https://github.com/IonBazan/aliyun-http-signer/actions)
[![PHP version](https://img.shields.io/packagist/php-v/ion-bazan/aliyun-http-signer.svg)](https://packagist.org/packages/ion-bazan/aliyun-http-signer)
[![Codecov](https://img.shields.io/codecov/c/gh/IonBazan/aliyun-http-signer)](https://codecov.io/gh/IonBazan/aliyun-http-signer)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FIonBazan%2Faliyun-http-signer%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/IonBazan/aliyun-http-signer/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/IonBazan/aliyun-http-signer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/IonBazan/aliyun-http-signer/?branch=master)
[![Downloads](https://img.shields.io/packagist/dt/ion-bazan/aliyun-http-signer.svg)](https://packagist.org/packages/ion-bazan/aliyun-http-signer)
[![License](https://img.shields.io/packagist/l/ion-bazan/aliyun-http-signer.svg)](https://packagist.org/packages/ion-bazan/aliyun-http-signer)

This library implements [Alibaba Cloud API Gateway request signature](https://www.alibabacloud.com/help/doc-detail/29475.htm) calculation for [PSR-7](https://www.php-fig.org/psr/psr-7/) compatible requests.
It integrates with [Guzzle](https://github.com/guzzle/guzzle) and [HttPlug](https://github.com/php-http/httplug) but can be used with any PSR-7-compatible client.

# Installation
Use [Composer](https://getcomposer.org/) to install the package using:

```bash
composer require ion-bazan/aliyun-http-signer
```

# Usage

## Symfony integration

The easiest way to integrate the package with Symfony is using [GuzzleBundleAliyunSignerPlugin](https://github.com/IonBazan/GuzzleBundleAliyunSignerPlugin) with Guzzle Bundle.

To use it with HttplugBundle or any other Bundle, simply register `RequestSigner`, `Key` and `RequestSignerPlugin` as services and inject the credentials to the `Key` service.

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

## Sign an API request using HttPlug plugin

```php
<?php

require_once 'vendor/autoload.php';

use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use IonBazan\AliyunSigner\Key;
use IonBazan\AliyunSigner\RequestSigner;
use IonBazan\AliyunSigner\HttPlug\RequestSignerPlugin;

// Provide credentials
$appId = '12345678';
$secret = base64_encode('secret');

// Create signer and plugin
$signer = new RequestSigner(new Key($appId, $secret));
$plugin = new RequestSignerPlugin($signer);
$pluginClient = new PluginClient(
    HttpClientDiscovery::find(),
    [$plugin]
);

$pluginClient->sendRequest($request);
```

# Bugs & issues

If you found a bug or security vulnerability, please [open an issue](https://github.com/IonBazan/aliyun-http-signer/issues/new)

# Contributing

Please feel free to submit Pull Requests adding new features or fixing bugs.

Please note that code must follow PSR-1, PSR-2, PSR-4 and PSR-7.  

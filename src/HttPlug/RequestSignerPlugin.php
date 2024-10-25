<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\HttPlug;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use IonBazan\AliyunSigner\RequestSigner;
use Psr\Http\Message\RequestInterface;

class RequestSignerPlugin implements Plugin
{
    public function __construct(protected readonly RequestSigner $requestSigner)
    {
    }

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $request = $this->requestSigner->signRequest($request);

        return $next($request);
    }
}

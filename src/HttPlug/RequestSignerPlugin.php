<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\HttPlug;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use IonBazan\AliyunSigner\RequestSigner;
use Psr\Http\Message\RequestInterface;

class RequestSignerPlugin implements Plugin
{
    /** @var RequestSigner */
    protected $requestSigner;

    public function __construct(RequestSigner $requestSigner)
    {
        $this->requestSigner = $requestSigner;
    }

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $request = $this->requestSigner->signRequest($request);

        return $next($request);
    }
}

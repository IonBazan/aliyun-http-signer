<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Guzzle;

use Closure;
use IonBazan\AliyunSigner\RequestSigner;
use Psr\Http\Message\RequestInterface;

class RequestSignerMiddleware
{
    public function __construct(protected readonly RequestSigner $requestSigner)
    {
    }

    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $this->requestSigner->signRequest($request);

            return $handler($request, $options);
        };
    }
}

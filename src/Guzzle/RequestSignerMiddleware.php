<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Guzzle;

use Closure;
use IonBazan\AliyunSigner\RequestSigner;
use Psr\Http\Message\RequestInterface;

class RequestSignerMiddleware
{
    /** @var RequestSigner */
    protected $requestSigner;

    public function __construct(RequestSigner $requestSigner)
    {
        $this->requestSigner = $requestSigner;
    }

    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $this->requestSigner->signRequest($request);

            return $handler($request, $options);
        };
    }
}

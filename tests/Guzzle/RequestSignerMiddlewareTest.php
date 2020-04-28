<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Tests\Guzzle;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use IonBazan\AliyunSigner\Guzzle\RequestSignerMiddleware;
use IonBazan\AliyunSigner\RequestSigner;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestSignerMiddlewareTest extends TestCase
{
    public function testMiddlewareCallsSigner(): void
    {
        $signer = $this->createMock(RequestSigner::class);
        $response = $this->createMock(ResponseInterface::class);
        $stack = new HandlerStack(new MockHandler([$response]));
        $stack->push(new RequestSignerMiddleware($signer));
        $request = $this->createMock(RequestInterface::class);
        $signer->expects($this->once())
            ->method('signRequest')
            ->with($request);

        $stack->resolve()($request, []);
    }
}

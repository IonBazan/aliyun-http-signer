<?php

namespace IonBazan\AliyunSigner\Tests\HttPlug;

use Http\Client\Promise\HttpFulfilledPromise;
use Http\Promise\Promise;
use IonBazan\AliyunSigner\HttPlug\RequestSignerPlugin;
use IonBazan\AliyunSigner\RequestSigner;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestSignerPluginTest extends TestCase
{
    public function testPluginCallsSigner(): void
    {
        $signer = $this->createMock(RequestSigner::class);
        $plugin = new RequestSignerPlugin($signer);
        $verify = function (RequestInterface $request): Promise {
            return new HttpFulfilledPromise(
                $this->createMock(ResponseInterface::class)
            );
        };

        $request = $this->createMock(RequestInterface::class);
        $signer->expects($this->once())
            ->method('signRequest')
            ->with($request);

        $plugin->handleRequest($request, $verify, $verify);
    }
}

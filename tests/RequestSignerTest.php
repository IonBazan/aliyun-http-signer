<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Tests;

use DateTime;
use IonBazan\AliyunSigner\Digest\DigestInterface;
use IonBazan\AliyunSigner\Key;
use IonBazan\AliyunSigner\RequestSigner;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class RequestSignerTest extends TestCase
{
    /**
     * @dataProvider requestDataProvider
     */
    public function testRequestMessageSignature(RequestInterface $request, string $nonce, string $expectedMessage, string $bodyMd5 = ''): void
    {
        $digest = $this->createMock(DigestInterface::class);
        $digest->expects($this->once())
            ->method('sign')
            ->with($expectedMessage, '5678')
            ->willReturn('dummy-signature');

        $requestSigner = new RequestSigner(new Key('1234', '5678'), $digest);
        $signedRequest = $requestSigner->signRequest($request, new DateTime('2020-04-28'), $nonce);

        $this->assertSignedRequest(
            $signedRequest,
            $nonce,
            1588032000000,
            'dummy-signature',
            $bodyMd5
        );
    }

    public function requestDataProvider(): array
    {
        $streamFactory = new StreamFactory();

        return [
            'GET with empty body and no query string' => [
                new Request(
                    'https://example.com/v1.0/category/123/products',
                    'get',
                    'php://memory',
                    ['Accept' => '*/*', 'Content-Type' => 'application/json']
                ),
                'test-nonce',
                <<<MESSAGE
GET
*/*

application/json
1588032000000
x-ca-key:1234
x-ca-nonce:test-nonce
x-ca-signaturemethod:HmacSHA256
x-ca-timestamp:1588032000000
/v1.0/category/123/products
MESSAGE
                ,
            ],
            'POST with JSON body query string' => [
                new Request(
                    'https://example.com/v1.0/category/123/products?page=10',
                    'post',
                    $streamFactory->createStream(json_encode(['hello' => 'world'])),
                    ['Accept' => '*/*', 'Content-Type' => 'application/json']
                ),
                'test-nonce',
                <<<MESSAGE
POST
*/*
+8JLzHoXlHWPwTJ/z+va9g==
application/json
1588032000000
x-ca-key:1234
x-ca-nonce:test-nonce
x-ca-signaturemethod:HmacSHA256
x-ca-timestamp:1588032000000
/v1.0/category/123/products?page=10
MESSAGE
                ,
                '+8JLzHoXlHWPwTJ/z+va9g==',
            ],
        ];
    }

    public function testRequestSignatureWithFixedNonceAndDateWithStage(): void
    {
        $requestSigner = new RequestSigner(new Key('1234', '5678'));
        $request = new Request(
            'https://example.com/v1.0/category/123/products?page=10',
            'GET',
            'php://memory',
            [
                'Accept'       => '*/*',
                'Content-Type' => 'application/json',
                'X-Ca-Stage'   => 'test',
            ]
        );
        $signedRequest = $requestSigner->signRequest($request, new DateTime('2020-04-30'), '');
        $this->assertSignedRequest(
            $signedRequest,
            '',
            1588204800000,
            'VoQMES7AM9S8GpXH8xMi9kl7E5/Xb4wsdL+jhNTnO08=',
            '',
            'x-ca-key,x-ca-nonce,x-ca-signaturemethod,x-ca-stage,x-ca-timestamp'
        );
    }

    public function testRequestSignatureWithFixedNonceAndDateWithCustomHeaders(): void
    {
        $requestSigner = new RequestSigner(new Key('1234', '5678'));
        $requestSigner->setSignatureHeaders([]);
        $requestSigner->addSignatureHeader('Content-Type');
        $request = new Request(
            'https://example.com/v1.0/category/123/products?page=10',
            'GET',
            'php://memory',
            ['Accept' => '*/*', 'Content-Type' => 'application/json']
        );
        $signedRequest = $requestSigner->signRequest($request, new DateTime('2020-04-30'), '');
        $this->assertSignedRequest(
            $signedRequest,
            '',
            1588204800000,
            'ljGjut4dwYPHfsHjlUBB0aCphG0l2Ew6I0hEPDDT6/w=',
            '',
            'content-type'
        );
    }

    public function testRequestSignatureWithGeneratedNonceAndDate(): void
    {
        $requestSigner = new RequestSigner(new Key('1234', '5678'));
        $request = new Request(
            'https://example.com/v1.0/category/123/products?page=10',
            'GET',
            'php://memory',
            ['Accept' => '*/*', 'Content-Type' => 'application/json']
        );
        $signedRequest1 = $requestSigner->signRequest($request);
        usleep(2000);
        $signedRequest2 = $requestSigner->signRequest($request);

        $this->assertHeadersVary(
            $signedRequest1,
            $signedRequest2,
            [
                'Date',
                'X-Ca-Signature',
                'X-Ca-Nonce',
                'X-Ca-Timestamp',
            ]
        );

        $this->assertSame('1234', $signedRequest1->getHeaderLine('X-Ca-Key'));
        $this->assertSame('1234', $signedRequest2->getHeaderLine('X-Ca-Key'));
    }

    protected function assertSignedRequest(
        RequestInterface $request,
        string $nonce,
        int $timestamp,
        string $signature,
        string $contentMd5 = '',
        string $signatureHeaders = 'x-ca-key,x-ca-nonce,x-ca-signaturemethod,x-ca-timestamp'
    ): void {
        $this->assertSame($signature, $request->getHeaderLine('X-Ca-Signature'));
        $this->assertTrue($request->hasHeader('X-Ca-Nonce'));
        $this->assertSame($nonce, $request->getHeaderLine('X-Ca-Nonce'));
        $this->assertSame('1234', $request->getHeaderLine('X-Ca-Key'));
        $this->assertSame((string) $timestamp, $request->getHeaderLine('X-Ca-Timestamp'));
        $this->assertSame((string) $timestamp, $request->getHeaderLine('Date'));
        $this->assertSame('HmacSHA256', $request->getHeaderLine('X-Ca-SignatureMethod'));
        $this->assertTrue($request->hasHeader('Content-MD5'));
        $this->assertSame($contentMd5, $request->getHeaderLine('Content-MD5'));
        $this->assertSame($signatureHeaders, $request->getHeaderLine('X-Ca-Signature-Headers'));
    }

    protected function assertHeadersVary(RequestInterface $request1, RequestInterface $request2, array $headers): void
    {
        foreach ($headers as $header) {
            $this->assertTrue($request1->hasHeader($header));
            $this->assertTrue($request2->hasHeader($header));
            $this->assertNotEquals($request1->getHeaderLine($header), $request2->getHeaderLine($header));
        }
    }
}

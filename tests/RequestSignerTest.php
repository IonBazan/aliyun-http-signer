<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Tests;

use DateTime;
use DateTimeZone;
use IonBazan\AliyunSigner\Digest\DigestInterface;
use IonBazan\AliyunSigner\Key;
use IonBazan\AliyunSigner\RequestSigner;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class RequestSignerTest extends TestCase
{
    #[DataProvider('requestDataProvider')]
    public function testRequestMessageSignature(RequestInterface $request, string $nonce, string $expectedMessage, string $bodyMd5 = ''): void
    {
        $digest = $this->createMock(DigestInterface::class);
        $digest->expects($this->once())
            ->method('sign')
            ->with($expectedMessage, '5678')
            ->willReturn('dummy-signature');

        $digest->expects($this->once())->method('getMethod')->willReturn('HmacSHA256');

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

    public static function requestDataProvider(): array
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
                    Tue, 28 Apr 2020 00:00:00 GMT
                    x-ca-key:1234
                    x-ca-nonce:test-nonce
                    x-ca-signature-method:HmacSHA256
                    x-ca-timestamp:1588032000000
                    /v1.0/category/123/products
                    MESSAGE,
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
                    Tue, 28 Apr 2020 00:00:00 GMT
                    x-ca-key:1234
                    x-ca-nonce:test-nonce
                    x-ca-signature-method:HmacSHA256
                    x-ca-timestamp:1588032000000
                    /v1.0/category/123/products?page=10
                    MESSAGE,
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
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
                'X-Ca-Stage' => 'test',
            ]
        );
        $signedRequest = $requestSigner->signRequest($request, new DateTime('2020-04-30'), '');
        $this->assertSignedRequest(
            $signedRequest,
            '',
            1588204800000,
            'yIHfw2kU986NsOD+TynOqwkvPT6IWgMobUJ4jgPGkiw=',
            '',
            'x-ca-key,x-ca-nonce,x-ca-signature-method,x-ca-stage,x-ca-timestamp'
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
            'UQxRfgzHaPI9u531wJwUcujzftv9KG73L8knurpkT3E=',
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
        string $signatureHeaders = 'x-ca-key,x-ca-nonce,x-ca-signature-method,x-ca-timestamp',
    ): void {
        $this->assertSame($signature, $request->getHeaderLine('X-Ca-Signature'));
        $this->assertTrue($request->hasHeader('X-Ca-Nonce'));
        $this->assertSame($nonce, $request->getHeaderLine('X-Ca-Nonce'));
        $this->assertSame('1234', $request->getHeaderLine('X-Ca-Key'));
        $this->assertSame((string) $timestamp, $request->getHeaderLine('X-Ca-Timestamp'));
        $this->assertSame((new DateTime('@'. ($timestamp/1000)))->format(DATE_RFC7231), $request->getHeaderLine('Date'));
        $this->assertSame('HmacSHA256', $request->getHeaderLine('X-Ca-Signature-Method'));
        $this->assertTrue($request->hasHeader('Content-MD5'));
        $this->assertSame($contentMd5, $request->getHeaderLine('Content-MD5'));
        $this->assertSame($signatureHeaders, $request->getHeaderLine('X-Ca-Signature-Headers'));
    }

    public function testDateIsPresentedInUtc(): void
    {
        $requestSigner = new RequestSigner(new Key('1234', '5678'));
        $request = new Request(
            'https://example.com/v1.0/category/123/products?page=10',
            'GET',
            'php://memory',
            ['Accept' => '*/*', 'Content-Type' => 'application/json']
        );
        $signedRequest = $requestSigner->signRequest($request, new DateTime('2020-04-30 12:00:00', new DateTimeZone('Asia/Singapore')));
        $this->assertSame('Thu, 30 Apr 2020 04:00:00 GMT', $signedRequest->getHeaderLine('Date'));
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

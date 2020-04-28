<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner;

use DateTime;
use DateTimeInterface;
use IonBazan\AliyunSigner\Digest\Digest;
use IonBazan\AliyunSigner\Digest\DigestInterface;
use Psr\Http\Message\RequestInterface;
use Ramsey\Uuid\Uuid;

class RequestSigner
{
    public const HEADER_X_CA_SIGNATURE = 'X-Ca-Signature';
    public const HEADER_X_CA_SIGNATURE_METHOD = 'X-Ca-SignatureMethod';
    public const HEADER_X_CA_SIGNATURE_HEADERS = 'X-Ca-Signature-Headers';
    public const HEADER_X_CA_TIMESTAMP = 'X-Ca-Timestamp';
    public const HEADER_X_CA_NONCE = 'X-Ca-Nonce';
    public const HEADER_X_CA_KEY = 'X-Ca-Key';
    public const HEADER_X_CA_STAGE = 'X-Ca-Stage';
    public const HEADER_DATE = 'Date';
    public const HEADER_CONTENT_MD5 = 'Content-MD5';

    /** @var Key */
    private $key;

    /** @var Digest */
    private $digest;

    private $signatureHeaders = [
        self::HEADER_X_CA_KEY,
        self::HEADER_X_CA_NONCE,
        self::HEADER_X_CA_SIGNATURE_METHOD,
        self::HEADER_X_CA_TIMESTAMP,
        self::HEADER_X_CA_STAGE,
    ];

    public function __construct(Key $key, ?DigestInterface $digest = null)
    {
        $this->key = $key;
        $this->digest = $digest ?? new Digest();
    }

    public function signRequest(RequestInterface $request, ?DateTimeInterface $date = null, ?string $nonce = null): RequestInterface
    {
        $nonce = $nonce ?? Uuid::uuid4()->toString();

        $timestamp = ($date ?? new DateTime())->format('Uv');
        $body = $request->getBody()->getContents();
        $contentMd5 = \strlen($body) ? base64_encode(md5($body, true)) : '';

        $request = $request->withHeader(self::HEADER_DATE, $timestamp)
            ->withHeader(self::HEADER_CONTENT_MD5, $contentMd5)
            ->withHeader(self::HEADER_X_CA_SIGNATURE_METHOD, 'HmacSHA256')
            ->withHeader(self::HEADER_X_CA_TIMESTAMP, $timestamp)
            ->withHeader(self::HEADER_X_CA_KEY, $this->key->getId())
            ->withHeader(self::HEADER_X_CA_NONCE, $nonce);

        $headers = $this->getHeadersToSign($request);

        $textToSign = implode("\n", [
            strtoupper($request->getMethod()),
            $request->getHeaderLine('accept'),
            $contentMd5,
            $request->getHeaderLine('content-type'),
            $timestamp,
            implode("\n", $headers),
            $this->getUrlToSign($request),
        ]);

        $signature = $this->digest->sign($textToSign, $this->key->getSecret());

        return $request->withHeader(self::HEADER_X_CA_SIGNATURE, $signature)
            ->withHeader(self::HEADER_X_CA_SIGNATURE_HEADERS, implode(',', array_keys($headers)));
    }

    /**
     * @param string[] $headers
     */
    public function setSignatureHeaders(array $headers): void
    {
        $this->signatureHeaders = $headers;
    }

    public function addSignatureHeader(string $header): void
    {
        $this->signatureHeaders[] = $header;
    }

    protected function getUrlToSign(RequestInterface $request): string
    {
        $query = urldecode($request->getUri()->getQuery());

        return $request->getUri()->getPath().(\strlen($query) ? '?'.$query : '');
    }

    protected function getHeadersToSign(RequestInterface $request): array
    {
        $headersToSign = [];
        foreach ($this->signatureHeaders as $headerName) {
            $headerName = strtolower($headerName);
            if ($request->hasHeader($headerName)) {
                $headersToSign[$headerName] = sprintf('%s:%s', $headerName, $request->getHeaderLine($headerName));
            }
        }

        ksort($headersToSign);

        return $headersToSign;
    }
}

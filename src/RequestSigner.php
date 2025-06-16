<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use IonBazan\AliyunSigner\Digest\Digest;
use IonBazan\AliyunSigner\Digest\DigestInterface;
use Psr\Http\Message\RequestInterface;
use Ramsey\Uuid\Uuid;

use function sprintf;

class RequestSigner
{
    public const HEADER_X_CA_SIGNATURE = 'X-Ca-Signature';
    public const HEADER_X_CA_SIGNATURE_METHOD = 'X-Ca-Signature-Method';
    public const HEADER_X_CA_SIGNATURE_HEADERS = 'X-Ca-Signature-Headers';
    public const HEADER_X_CA_TIMESTAMP = 'X-Ca-Timestamp';
    public const HEADER_X_CA_NONCE = 'X-Ca-Nonce';
    public const HEADER_X_CA_KEY = 'X-Ca-Key';
    public const HEADER_X_CA_STAGE = 'X-Ca-Stage';
    public const HEADER_DATE = 'Date';
    public const HEADER_CONTENT_MD5 = 'Content-MD5';

    private array $signatureHeaders = [
        self::HEADER_X_CA_KEY,
        self::HEADER_X_CA_NONCE,
        self::HEADER_X_CA_SIGNATURE_METHOD,
        self::HEADER_X_CA_TIMESTAMP,
        self::HEADER_X_CA_STAGE,
    ];

    public function __construct(private readonly Key $key, private readonly DigestInterface $digest = new Digest())
    {
    }

    public function signRequest(RequestInterface $request, ?DateTimeInterface $date = null, ?string $nonce = null): RequestInterface
    {
        $nonce ??= Uuid::uuid4()->toString();

        $date = DateTime::createFromInterface($date ?? new DateTime())->setTimezone(new DateTimeZone('UTC'));
        $timeString = $date->format(DATE_RFC7231);

        $body = $request->getBody()->getContents();
        $contentMd5 = $body !== '' ? base64_encode(md5($body, true)) : '';

        $request = $request->withHeader(self::HEADER_DATE, $timeString)
            ->withHeader(self::HEADER_CONTENT_MD5, $contentMd5)
            ->withHeader(self::HEADER_X_CA_SIGNATURE_METHOD, 'HmacSHA256')
            ->withHeader(self::HEADER_X_CA_TIMESTAMP, $date->format('Uv'))
            ->withHeader(self::HEADER_X_CA_KEY, $this->key->id)
            ->withHeader(self::HEADER_X_CA_NONCE, $nonce);

        $headers = $this->getHeadersToSign($request);

        $textToSign = implode("\n", [
            strtoupper($request->getMethod()),
            $request->getHeaderLine('accept'),
            $contentMd5,
            $request->getHeaderLine('content-type'),
            $timeString,
            implode("\n", $headers),
            $this->getUrlToSign($request),
        ]);

        $signature = $this->digest->sign($textToSign, $this->key->secret);

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

        return $request->getUri()->getPath().($query !== '' ? '?'.$query : '');
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

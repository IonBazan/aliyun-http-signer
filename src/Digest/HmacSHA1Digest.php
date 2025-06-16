<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Digest;

class HmacSHA1Digest implements DigestInterface
{
    public function sign(string $message, string $secret): string
    {
        return base64_encode(hash_hmac('sha1', $message, $secret, true));
    }

    public function getMethod(): string
    {
        return 'HmacSHA1';
    }
}

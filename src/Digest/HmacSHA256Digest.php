<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Digest;

class HmacSHA256Digest implements DigestInterface
{
    public function sign(string $message, string $secret): string
    {
        return base64_encode(hash_hmac('sha256', $message, $secret, true));
    }

    public function getMethod(): string
    {
        return 'HmacSHA256';
    }
}

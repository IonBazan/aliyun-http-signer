<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Digest;

interface DigestInterface
{
    public function sign(string $message, string $secret): string;

    public function getMethod(): string;
}

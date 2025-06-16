<?php

declare(strict_types=1);


use IonBazan\AliyunSigner\Digest\HmacSHA256Digest;
use PHPUnit\Framework\TestCase;

class HmacSHA256DigestTest extends TestCase
{
    public function testValidSignature(): void
    {
        $digest = new HmacSHA256Digest();
        $this->assertSame('q6qHaHxICqoanwKB290Z/pZ8WfY610HMMDH3LjB9sSc=', $digest->sign('test-message', 'secret1234'));
        $this->assertSame('HmacSHA256', $digest->getMethod());
    }
}

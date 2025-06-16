<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Tests;

use IonBazan\AliyunSigner\Digest\HmacSHA1Digest;
use PHPUnit\Framework\TestCase;

class HmacSHA1DigestTest extends TestCase
{
    public function testValidSignature(): void
    {
        $digest = new HmacSHA1Digest();
        $this->assertSame('TKZWWENohi3oRi51qMM45XOOPco=', $digest->sign('test-message', 'secret1234'));
        $this->assertSame('HmacSHA1', $digest->getMethod());
    }
}

<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner\Tests;

use IonBazan\AliyunSigner\Digest\Digest;
use PHPUnit\Framework\TestCase;

class DigestTest extends TestCase
{
    public function testValidSignature(): void
    {
        $digest = new Digest();
        $this->assertSame('q6qHaHxICqoanwKB290Z/pZ8WfY610HMMDH3LjB9sSc=', $digest->sign('test-message', 'secret1234'));
    }
}

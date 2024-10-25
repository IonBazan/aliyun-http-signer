<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner;

use SensitiveParameter;

class Key
{
    public function __construct(
        public readonly string $id,
        #[SensitiveParameter]
        public readonly string $secret,
    ) {
    }
}

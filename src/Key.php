<?php

declare(strict_types=1);

namespace IonBazan\AliyunSigner;

class Key
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $secret;

    public function __construct(string $id, string $secret)
    {
        $this->id = $id;
        $this->secret = $secret;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}

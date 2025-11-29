<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw\Fixtures;

final class Bar
{
    public function __construct(private readonly string $foo) {}

    public function baz(string $token): string
    {
        return $this->foo.$token;
    }
}

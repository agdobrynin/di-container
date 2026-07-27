<?php

declare(strict_types=1);

namespace Tests\Compiler\CompileLazyObject\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isLazy: true)]
final class Qux
{
    public function __construct(private string $val = 'Lorem ipsum in Qux') {}

    public function getVal(): string
    {
        return $this->val;
    }
}

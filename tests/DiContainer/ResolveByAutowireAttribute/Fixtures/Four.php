<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByAutowireAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isLazy: true)]
final class Four
{
    public function __construct(private string $foo = 'Lorem ipsum') {}

    public function getFoo(): string
    {
        return $this->foo;
    }
}

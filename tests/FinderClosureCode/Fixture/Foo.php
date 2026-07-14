<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

final class Foo
{
    public function __construct(private string $foo = 'Lorem ipsum') {}

    public function getFoo(): string
    {
        return $this->foo;
    }
}

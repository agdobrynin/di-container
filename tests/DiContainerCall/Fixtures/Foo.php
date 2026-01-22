<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\Fixtures;

final class Foo
{
    public function __construct(public readonly string $baz) {}

    public function bar(): string
    {
        return $this->baz.' bar';
    }
}

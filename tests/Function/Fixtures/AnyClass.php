<?php

declare(strict_types=1);

namespace Tests\Function\Fixtures;

final class AnyClass
{
    public function __construct(private string $foo) {}

    public function foo(string $baz): void {}
}

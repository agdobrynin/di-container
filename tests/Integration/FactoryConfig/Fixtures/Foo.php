<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

final class Foo
{
    public function __construct(public readonly string $str) {}
}

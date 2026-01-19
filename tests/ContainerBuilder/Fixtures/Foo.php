<?php

declare(strict_types=1);

namespace Tests\ContainerBuilder\Fixtures;

final class Foo
{
    public function __construct(public readonly string $bar) {}
}

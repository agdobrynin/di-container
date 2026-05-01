<?php

declare(strict_types=1);

namespace Tests\Integration\ContainerParameter\Fixtures;

class Foo
{
    public function __construct(public readonly Bar $bar, public readonly string $endpoint) {}
}

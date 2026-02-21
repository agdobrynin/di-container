<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

final class FooFactory
{
    public function __construct(private Bar $bar) {}

    public function make(string $info = ''): mixed
    {
        return $info ?: $this->bar->str;
    }
}

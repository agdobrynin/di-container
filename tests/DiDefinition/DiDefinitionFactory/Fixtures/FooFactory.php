<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

final class FooFactory
{
    public function __construct(private Bar $bar) {}

    public function make(): mixed
    {
        return $this->bar->str;
    }
}

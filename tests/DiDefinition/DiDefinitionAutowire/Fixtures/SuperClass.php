<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

class SuperClass
{
    public function __construct(private string $dependency) {}

    public function getDependency(): string
    {
        return $this->dependency;
    }
}

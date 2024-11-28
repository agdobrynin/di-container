<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByInterface\Fixtures;

class SuperClass implements SuperInterface
{
    public function __construct(private string $dependency) {}

    public function getDependency(): string
    {
        return $this->dependency;
    }
}

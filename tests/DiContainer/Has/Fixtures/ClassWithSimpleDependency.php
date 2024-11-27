<?php

declare(strict_types=1);

namespace Tests\DiContainer\Has\Fixtures;

class ClassWithSimpleDependency
{
    public function __construct(private string $dependency) {}
}

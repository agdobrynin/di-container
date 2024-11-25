<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver\Fixtures;

class ClassWithDependency
{
    public function __construct(public string $dependency) {}
}

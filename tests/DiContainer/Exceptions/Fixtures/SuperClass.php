<?php

declare(strict_types=1);

namespace Tests\DiContainer\Exceptions\Fixtures;

class SuperClass
{
    public function __construct(public DependencyClass $dependency) {}
}

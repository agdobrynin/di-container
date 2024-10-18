<?php

declare(strict_types=1);

namespace Tests\Unit\Container\CallCircularDependency\Fixtures;

class FirstClass
{
    public function __construct(public SecondClass $class) {}
}

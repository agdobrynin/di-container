<?php

declare(strict_types=1);

namespace Tests\Unit\Container\CallCircularDependency\Fixtures;

class ThirdClass
{
    public function __construct(public FirstClass $class) {}
}

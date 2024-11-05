<?php

declare(strict_types=1);

namespace Tests\Unit\CallCircularDependency\Fixtures;

class SecondClass
{
    public function __construct(public ThirdClass $class) {}
}

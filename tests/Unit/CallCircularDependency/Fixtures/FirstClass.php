<?php

declare(strict_types=1);

namespace Tests\Unit\CallCircularDependency\Fixtures;

class FirstClass implements CircularClassByInterface, CircularClassByInterfaceInject
{
    public function __construct(public SecondClass $class) {}

    public function __invoke(): SecondClass
    {
        return $this->class;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

class ClassWithMethodWithParameterNonVariadicByDiFactory
{
    public function __construct() {}

    public function myMethod(
        #[DiFactory(ClassDependencyOnPropertyDiFactory::class)]
        ClassDependency $dependency
    ): string {
        return $dependency->name;
    }

    public function myMethodFail(
        #[DiFactory(ClassDependencyOnPropertyDiFactory::class)]
        #[DiFactory(ClassDependencyOnPropertyDiFactory::class)]
        ClassDependency $dependency
    ): string {
        return $dependency->name;
    }
}

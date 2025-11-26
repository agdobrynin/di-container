<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

use Kaspi\DiContainer\Attributes\DiFactory;
use ReflectionClass;

trait SomeTrait
{
    private function getDiFactoryAttribute(ReflectionClass $reflectionClass): ?DiFactory
    {
        return new DiFactory($reflectionClass->name);
    }
}

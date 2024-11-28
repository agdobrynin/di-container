<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class MyClassDiFactory implements DiFactoryInterface
{
    public function __construct(private DependencyClass $dependencyClass) {}

    public function __invoke(ContainerInterface $container): MyClass
    {
        return new MyClass($this->dependencyClass);
    }
}

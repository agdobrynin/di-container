<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class MyClassMaker implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): MyClass
    {
        return new MyClass(
            new DependencyClass('secure_string')
        );
    }
}

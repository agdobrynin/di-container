<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class MyClassSingletonDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): MyClassSingleton
    {
        return new MyClassSingleton(
            new DependencyClass($container->get('security.key'))
        );
    }
}

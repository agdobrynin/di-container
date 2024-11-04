<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class ClassDependencyOnPropertyDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): ClassDependency
    {
        return new ClassDependency('make from '.self::class);
    }
}

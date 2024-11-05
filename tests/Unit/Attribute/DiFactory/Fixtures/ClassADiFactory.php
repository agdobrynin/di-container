<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class ClassADiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): ClassA
    {
        return new ClassA(new ClassDependency('make from '.self::class));
    }
}

<?php

declare(strict_types=1);

namespace Tests\AttributeReader\AttributeOnParameter\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class FooFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): Foo
    {
        return new Foo();
    }
}

<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\ImportCreating\Factory;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;
use Tests\DefinitionsLoader\Fixtures\ImportCreating\Foo;

final class FactoryFoo implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): Foo
    {
        return new Foo('secure_string');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;

class FactoryClassWithFactoryArgument implements FactoryInterface
{
    public function __invoke(ContainerInterface $container): \ArrayIterator
    {
        return new \ArrayIterator(
            $container->has('names') ? $container->get('names') : []
        );
    }
}

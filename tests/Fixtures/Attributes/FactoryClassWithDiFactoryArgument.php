<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class FactoryClassWithDiFactoryArgument implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): \ArrayIterator
    {
        return new \ArrayIterator(
            $container->has('names') ? $container->get('names') : []
        );
    }
}

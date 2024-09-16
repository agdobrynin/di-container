<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;

class FactorySuperClass implements FactoryInterface
{
    public function __invoke(ContainerInterface $container): SuperClass
    {
        return new SuperClass('Piter', 22);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class DiFactorySuperClass implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): SuperClass
    {
        return new SuperClass('Piter', 22);
    }
}

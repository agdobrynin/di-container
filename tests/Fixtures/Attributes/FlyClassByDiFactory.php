<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class FlyClassByDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): FlyClass
    {
        return new FlyClass();
    }
}

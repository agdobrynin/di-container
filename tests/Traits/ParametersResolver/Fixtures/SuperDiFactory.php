<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class SuperDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): mixed
    {
        return [
            new MoreSuperClass(),
            new SuperClass(),
        ];
    }
}

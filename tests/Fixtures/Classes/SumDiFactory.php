<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class SumInterfaceDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): Sum
    {
        return new Sum();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;
use Tests\Fixtures\Classes\Interfaces\SumInterface;

class SumInterfaceFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): SumInterface
    {
        return new Sum();
    }
}

<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiFactory\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class MainFirstDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): Main
    {
        return new Main();
    }
}

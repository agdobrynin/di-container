<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class MyDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): mixed
    {
        return 'ok';
    }
}

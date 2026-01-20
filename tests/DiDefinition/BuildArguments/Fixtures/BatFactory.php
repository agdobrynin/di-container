<?php

declare(strict_types=1);

namespace Tests\DiDefinition\BuildArguments\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class BatFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): Bat
    {
        return new Bat('Lorem ipsum');
    }
}

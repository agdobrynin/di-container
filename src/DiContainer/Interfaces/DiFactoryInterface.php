<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerInterface;

interface DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): mixed;
}

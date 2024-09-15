<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerInterface;

/**
 * @template T of object
 */
interface FactoryInterface
{
    public function __invoke(ContainerInterface $container): object;
}

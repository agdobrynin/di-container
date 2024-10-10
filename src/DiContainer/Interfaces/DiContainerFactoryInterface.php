<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerExceptionInterface;

interface DiContainerFactoryInterface
{
    /**
     * @param iterable<string, mixed> $definitions
     *
     * @throws ContainerExceptionInterface
     */
    public function make(
        iterable $definitions = [],
    ): DiContainerInterface;
}

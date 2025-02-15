<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Psr\Container\ContainerExceptionInterface;

interface DiContainerFactoryInterface
{
    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @throws ContainerExceptionInterface
     */
    public function make(
        iterable $definitions = [],
    ): DiContainerInterface;
}

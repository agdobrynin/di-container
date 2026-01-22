<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;

interface DiContainerFactoryInterface
{
    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface|ContainerIdentifierExceptionInterface
     */
    public function make(
        iterable $definitions = [],
    ): DiContainerInterface;
}

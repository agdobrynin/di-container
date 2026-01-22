<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiContainerGetterDefinitionInterface
{
    /**
     * Get container definition via container identifier.
     *
     * Result definition maybe to create even definition not defined in container
     * when container configuration switch on option "use zero configuration definition".
     *
     *   `\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface::isUseZeroConfigurationDefinition()`.
     *
     * @param non-empty-string $id container identifier
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    public function getDefinition(string $id): DiDefinitionInterface;
}

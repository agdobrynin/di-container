<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\ParametersResolverInterface;
use Psr\Container\ContainerExceptionInterface;

interface DiDefinitionAutowireInterface extends DiDefinitionInterface
{
    public function getContainerId(): string;

    /**
     * Defined arguments by configuration.
     */
    public function getArguments(): array;

    public function isSingleton(): bool;

    /**
     * @throws AutowiredExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function invoke(ParametersResolverInterface $parametersResolver): mixed;
}

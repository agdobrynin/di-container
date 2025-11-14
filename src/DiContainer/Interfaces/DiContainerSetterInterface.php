<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

interface DiContainerSetterInterface
{
    /**
     * @param class-string|non-empty-string                                                                                                                                                                                                                                $id
     * @param DiDefinitionArgumentsInterface|DiDefinitionAutowireInterface|DiDefinitionInterface|DiDefinitionSetupAutowireInterface|DiDefinitionSingletonInterface|DiDefinitionTagArgumentInterface|DiDefinitionTaggedAsInterface|DiTaggedDefinitionInterface|mixed|object $definition
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface
     * @throws DiDefinitionExceptionInterface
     */
    public function set(string $id, mixed $definition): static;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

interface DiContainerSetterInterface
{
    /**
     * @param class-string|non-empty-string                                                                   $id
     * @param DiDefinitionInterface|DiDefinitionInvokableInterface|DiDefinitionTaggedAsInterface|mixed|object $definition
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface
     * @throws DiDefinitionExceptionInterface
     */
    public function set(string $id, mixed $definition): static;
}

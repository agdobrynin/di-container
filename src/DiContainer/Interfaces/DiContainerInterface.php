<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionAutowireInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiContainerInterface extends ContainerInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T>|string $id
     *
     * @return T
     *
     * @throws NotFoundExceptionInterface  no entry was found for **this** identifier
     * @throws ContainerExceptionInterface Error while retrieving the entry.*
     */
    public function get(string $id): mixed;

    /**
     * Get definitions form container.
     *
     * @return iterable<non-empty-string, DiDefinitionInterface|DiDefinitionInvokableInterface|DiDefinitionLinkInterface|DiDefinitionTaggedAsInterface|DiTaggedDefinitionAutowireInterface>
     */
    public function getDefinitions(): iterable;

    public function getConfig(): ?DiContainerConfigInterface;
}

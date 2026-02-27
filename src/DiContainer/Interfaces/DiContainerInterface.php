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
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
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
     *
     * @phpstan-ignore method.templateTypeNotInParameter
     */
    public function get(string $id): mixed;

    /**
     * Get definitions form container.
     *
     * @return iterable<non-empty-string, DiDefinitionArgumentsInterface|DiDefinitionAutowireInterface|DiDefinitionInterface|DiDefinitionSetupAutowireInterface|DiDefinitionSingletonInterface|DiDefinitionTagArgumentInterface|DiDefinitionTaggedAsInterface|DiTaggedDefinitionInterface>
     */
    public function getDefinitions(): iterable;

    public function getConfig(): DiContainerConfigInterface;

    /**
     * @param non-empty-string $tag
     *
     * @return iterable<non-empty-string, (DiDefinitionAutowireInterface&DiTaggedDefinitionInterface)|DiTaggedDefinitionInterface>
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function findTaggedDefinitions(string $tag): iterable;

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

    /**
     * Returns container identifiers mark as deleted from  resolving.
     *
     * @return iterable<class-string|non-empty-string, true>
     */
    public function getRemovedDefinitionIds(): iterable;
}

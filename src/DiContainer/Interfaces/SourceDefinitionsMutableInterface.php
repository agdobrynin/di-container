<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use IteratorAggregate;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Traversable;

interface SourceDefinitionsMutableInterface extends IteratorAggregate
{
    public function has(string $id): bool;

    public function get(string $id): ?DiDefinitionInterface;

    /**
     * @throws ContainerAlreadyRegisteredExceptionInterface
     * @throws ContainerIdentifierExceptionInterface
     * @throws DiDefinitionExceptionInterface
     */
    public function set(int|string $id, mixed $value): void;

    /**
     * @return Traversable<non-empty-string, DiDefinitionInterface>
     */
    public function getIterator(): Traversable;

    /**
     * @param non-empty-string $id
     */
    public function isRemovedDefinition(string $id): bool;

    /**
     * @return iterable<class-string|non-empty-string, true>
     */
    public function getRemovedDefinitionIds(): iterable;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Iterator;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\ContainerIdentifierExistExceptionInterface;
use Kaspi\DiContainer\Interfaces\ResetInterface;

interface CompiledEntriesInterface extends ResetInterface
{
    /**
     * Add container identifier from container method `has()`.
     *
     * @param non-empty-string $id
     */
    public function addNotFoudContainerId(string $id): void;

    public function hasNotFoudContainerId(string $id): bool;

    /**
     * @param non-empty-string $id
     *
     * @throws ContainerIdentifierExistExceptionInterface container identifier already exist
     */
    public function setServiceMethod(string $id, CompiledEntryInterface $compiledEntry): void;

    /**
     * Return container identifiers for container method `has()`.
     *
     * @return Iterator<int, non-empty-string>
     */
    public function getHasIdentifiers(): Iterator;

    /**
     * @return Iterator<int, array{id: non-empty-string, serviceMethod: non-empty-string}>
     */
    public function getContainerIdentifierMappedMethodResolve(): Iterator;

    /**
     * @return Iterator<int, array{id: non-empty-string, serviceMethod: non-empty-string, entry: CompiledEntryInterface}>
     */
    public function getCompiledEntries(): Iterator;
}

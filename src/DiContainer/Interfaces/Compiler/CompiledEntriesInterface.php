<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Iterator;
use Kaspi\DiContainer\Interfaces\ResetInterface;

interface CompiledEntriesInterface extends ResetInterface
{
    /**
     * Add container identifier from container method `has()`.
     *
     * @param non-empty-string $id
     */
    public function addNotFoudContainerId(string $id): void;

    /**
     * @param non-empty-string $serviceMethod
     */
    public function hasServiceMethod(string $serviceMethod): bool;

    /**
     * @param non-empty-string $serviceMethod
     * @param non-empty-string $containerIdentifier
     */
    public function setServiceMethod(string $serviceMethod, string $containerIdentifier, CompiledEntryInterface $compiledEntry): void;

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

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;

interface DefinitionsConfiguratorInterface
{
    /**
     * Remove any definitions from configuration files or from importing classes.
     *
     * @param non-empty-string $id
     */
    public function removeDefinition(string $id): void;

    /**
     * Get container identifiers for definitions removed via `self::removeDefinition()`.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function getRemovedDefinitionIds(): array;

    /**
     * Overwrites any definitions from configuration files or from importing classes.
     *
     * @param non-empty-string $id
     */
    public function setDefinition(string $id, mixed $definition): void;

    /**
     * Find a definition via container identifier.
     *
     * @param non-empty-string $id
     *
     * @throws DefinitionsLoaderExceptionInterface
     */
    public function getDefinition(string $id): mixed;

    /**
     * Load definitions from configuration files.
     *
     * @param non-empty-string $file
     * @param non-empty-string ...$_
     */
    public function load(string $file, string ...$_): void;

    /**
     * Load definitions from configuration files with override previous definition.
     *
     * @param non-empty-string $file
     * @param non-empty-string ...$_
     */
    public function loadOverride(string $file, string ...$_): void;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;

interface DefinitionsConfiguratorInterface
{
    /**
     * Remove a definition from configuration files or from importing classes via container identifier.
     *
     * @param non-empty-string $id
     */
    public function removeDefinition(string $id): void;

    /**
     * @return iterable<class-string|non-empty-string, DiDefinitionInterface|mixed>
     */
    public function getDefinitions(): iterable;

    /**
     * Sets new definition for container identifier.
     *
     * @param non-empty-string $id
     */
    public function setDefinition(string $id, mixed $definition): void;

    /**
     * Finds the container definition via container identifier.
     *
     * Returns an object implementing `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface`,
     * if no definition is found will be returns null.
     *
     * @param non-empty-string $id
     */
    public function getDefinition(string $id): ?DiDefinitionInterface;

    /**
     * @param non-empty-string $tag
     *
     * @return iterable<non-empty-string, DiTaggedDefinitionInterface>
     */
    public function findTaggedDefinition(string $tag): iterable;

    /**
     * Loads definitions from configuration files.
     *
     * @param non-empty-string $file
     * @param non-empty-string ...$_
     *
     * @throws DefinitionsLoaderExceptionInterface
     */
    public function load(string $file, string ...$_): void;

    /**
     * Loads definitions from configuration files and override exist definitions with same container identifiers.
     *
     * @param non-empty-string $file
     * @param non-empty-string ...$_
     *
     * @throws DefinitionsLoaderExceptionInterface
     */
    public function loadOverride(string $file, string ...$_): void;
}

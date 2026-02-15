<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;

interface DefinitionsConfiguratorInterface
{
    /**
     * Remove any definitions from configuration files or from importing classes.
     *
     * @param non-empty-string $id
     */
    public function removeDefinition(string $id): void;

    /**
     * Overwrites any definitions from configuration files or from importing classes.
     *
     * @param non-empty-string $id
     */
    public function setDefinition(string $id, mixed $definition): void;

    /**
     * Find a container definition via container identifier.
     *
     * Returns an object implementing `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface`,
     * if no definition is found will be returns null.
     *
     * @param non-empty-string $id
     */
    public function getDefinition(string $id): ?DiDefinitionInterface;

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

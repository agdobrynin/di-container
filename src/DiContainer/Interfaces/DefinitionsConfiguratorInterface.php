<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use OutOfBoundsException;

interface DefinitionsConfiguratorInterface
{
    public function hasDefinition(string $id): bool;

    /**
     * @param non-empty-string $id
     *
     * @throws OutOfBoundsException
     */
    public function getDefinition(string $id): mixed;

    /**
     * @param non-empty-string $id
     *
     * @return $this
     */
    public function setDefinition(string $id, mixed $value): static;

    /**
     * @param non-empty-string $id
     *
     * @throws OutOfBoundsException
     */
    public function removeDefinition(string $id): void;

    /**
     * @param non-empty-string $tag tag name
     *
     * @return iterable<non-empty-string, DiDefinitionInterface>
     */
    public function findTaggedDefinitions(string $tag): iterable;

    /**
     * Load definitions from configuration files.
     *
     * @param non-empty-string $file
     * @param non-empty-string ...$_
     *
     * @return $this
     */
    public function load(string $file, string ...$_): static;

    /**
     * Load definitions from configuration files with override previous definition.
     *
     * @param non-empty-string $file
     * @param non-empty-string ...$_
     *
     * @return $this
     */
    public function loadOverride(string $file, string ...$_): static;
}

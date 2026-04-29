<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\NotFoundDefinitionInterface;
use UnitEnum;

/**
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 */
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
     * @param non-empty-string            $id
     * @param callable(string $id): mixed $fallback
     *
     * @throws NotFoundDefinitionInterface
     */
    public function getDefinition(string $id, ?callable $fallback = null): mixed;

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

    /**
     * Loads container parameters.
     *
     * @param non-empty-string $file
     * @param non-empty-string ...$_
     *
     * @throws DefinitionsLoaderExceptionInterface
     */
    public function loadParameters(string $file, string ...$_): void;

    /**
     * @param iterable<non-empty-string, mixed> $parameters
     */
    public function addParameters(iterable $parameters): void;

    /**
     * @param non-empty-string    $name
     * @param SourceParameterType $value
     */
    public function setParameter(string $name, array|bool|float|int|string|UnitEnum|null $value): void;

    /**
     * @param non-empty-string $name
     */
    public function removeParameter(string $name): void;

    public function hasParameter(string $name): bool;

    /**
     * @template T of object
     * Configurator context.
     *
     * Provides any additional data for the configuration file.
     *
     * @param class-string<T>|non-empty-string   $name
     * @param null|callable(string $name): mixed $fallback
     *
     * @return ($name is class-string<T> ? T : mixed)
     *
     * @throws DefinitionsLoaderExceptionInterface
     */
    public function getContext(string $name, ?callable $fallback = null): mixed;
}

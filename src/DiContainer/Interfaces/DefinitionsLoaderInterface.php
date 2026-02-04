<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;

interface DefinitionsLoaderInterface extends ResetInterface
{
    /**
     * Load definitions from configuration files.
     *
     * Method trow exception if container identifier already is registered.
     *
     * @param non-empty-string ...$file
     *
     * @return $this
     *
     * @throws DefinitionsLoaderExceptionInterface
     */
    public function load(string ...$file): static;

    /**
     * Load definitions from configuration files and override exist definitions.
     *
     * @param non-empty-string ...$file
     *
     * @return $this
     *
     * @throws DefinitionsLoaderExceptionInterface
     */
    public function loadOverride(string ...$file): static;

    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @return $this
     *
     * @throws ContainerAlreadyRegisteredException|DefinitionsLoaderExceptionInterface
     */
    public function addDefinitions(bool $overrideDefinitions, iterable $definitions): static;

    /**
     * @return iterable<class-string|non-empty-string, DiDefinitionInterface|mixed>
     *
     * @throws DefinitionsLoaderExceptionInterface
     */
    public function definitions(): iterable;

    /**
     * Import classes from directories.
     *
     * @param non-empty-string       $namespace           PSR-4 namespace prefix
     * @param non-empty-string       $src                 source directory
     * @param list<non-empty-string> $excludeFiles        exclude files matching by pattern
     * @param list<non-empty-string> $availableExtensions available files extensions, empty list available all files
     *
     * @return $this
     *
     * @throws DefinitionsLoaderExceptionInterface
     */
    public function import(string $namespace, string $src, array $excludeFiles = [], array $availableExtensions = ['php']): static;

    /**
     * Using php attributes for configure services.
     */
    public function useAttribute(bool $useAttribute): static;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use InvalidArgumentException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

interface DefinitionsLoaderInterface
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
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws ContainerExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function load(string ...$file): static;

    /**
     * Load definitions from configuration files and override exist definitions.
     *
     * @param non-empty-string ...$file
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws ContainerExceptionInterface
     */
    public function loadOverride(string ...$file): static;

    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @return $this
     *
     * @throws DiDefinitionExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function addDefinitions(bool $overrideDefinitions, iterable $definitions): static;

    /**
     * @return iterable<class-string|non-empty-string, DiDefinitionInterface|mixed>
     *
     * @throws DiDefinitionExceptionInterface
     * @throws AutowireExceptionInterface
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function definitions(): iterable;

    /**
     * Import classes from directories.
     *
     * @param non-empty-string       $namespace                 PSR-4 namespace prefix
     * @param non-empty-string       $src                       source directory
     * @param list<non-empty-string> $excludeFilesRegExpPattern exclude files matching by regexp pattern
     * @param list<non-empty-string> $availableExtensions       available files extensions, empty list available all files
     * @param bool                   $useAttribute              using php attributes for configure services from import source directory
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function import(string $namespace, string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php'], bool $useAttribute = true): static;
}

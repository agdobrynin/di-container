<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface;
use UnitEnum;

/**
 * @phpstan-type SourceParameterType null|scalar|UnitEnum|(null|scalar|UnitEnum)[]
 */
interface DiContainerBuilderInterface
{
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
     * Load definitions from configuration files and override exist definitions.
     *
     * @param non-empty-string $file
     * @param non-empty-string ...$_
     *
     * @return $this
     */
    public function loadOverride(string $file, string ...$_): static;

    /**
     * Add definitions.
     *
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @return $this
     */
    public function addDefinitions(iterable $definitions): static;

    /**
     * Add definitions, overwriting previously added ones.
     *
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @return $this
     */
    public function addDefinitionsOverride(iterable $definitions): static;

    /**
     * Load container parameters from configuration files and override exist parameters.
     *
     * @param non-empty-string $file
     * @param non-empty-string ...$_
     *
     * @return $this
     */
    public function loadParameters(string $file, string ...$_): static;

    /**
     * Add container parameters from collection.
     *
     * @param iterable<non-empty-string, SourceParameterType> $parameters
     *
     * @return $this
     */
    public function addParameters(iterable $parameters): static;

    /**
     * Add a container parameter with override exist parameter.
     *
     * @param non-empty-string    $name
     * @param SourceParameterType $value
     *
     * @return $this
     */
    public function setParameter(string $name, array|bool|float|int|string|UnitEnum|null $value): static;

    /**
     * Adds configurator contexts for configuration files.
     *
     * The collection is represented as key-value type elements,
     * where the key defines the context name as a non-empty string.
     *
     * The existing context name will be replaced.
     *
     * @param iterable<non-empty-string, mixed> $context
     *
     * @return $this
     */
    public function addConfiguratorContexts(iterable $context): static;

    /**
     * Sets the context value for configuration files.
     *
     * @param non-empty-string $name context name
     *
     * @return $this
     */
    public function setConfiguratorContext(string $name, mixed $context): static;

    /**
     * Import classes from directories.
     *
     * @param non-empty-string       $namespace           PSR-4 namespace prefix
     * @param non-empty-string       $src                 source directory
     * @param list<non-empty-string> $excludeFiles        exclude files matching by pattern
     * @param list<non-empty-string> $availableExtensions available files extensions, empty list available all files
     *
     * @return $this
     */
    public function import(string $namespace, string $src, array $excludeFiles = [], array $availableExtensions = ['php']): static;

    /**
     * @param non-empty-string                $outputDirectory     output directory for compiled container
     * @param class-string                    $containerClass      fully qualified class name for compiler container
     * @param bool                            $isExclusiveLockFile exclusive locking of the resulting file while the container compiler writes
     * @param array{array-key, mixed}|array{} $options             compiler additional option
     *
     * @return $this
     */
    public function compileToFile(string $outputDirectory, string $containerClass, int $permissionCompiledContainerFile = 0666, bool $isExclusiveLockFile = true, array $options = []): static;

    /**
     * Build dependency injection container.
     *
     * @throws ContainerBuilderExceptionInterface
     */
    public function build(): DiContainerCallInterface&DiContainerInterface&DiContainerSetterInterface;
}

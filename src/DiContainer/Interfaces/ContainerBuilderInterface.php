<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;

interface ContainerBuilderInterface
{
    /**
     * Set custom container configuration.
     *
     * @return $this
     */
    public function setDiContainerConfig(DiContainerConfigInterface $config): static;

    public function getDiContainerConfig(): DiContainerConfigInterface;

    /**
     * Load definitions from configuration files.
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
     */
    public function loadOverride(string ...$file): static;

    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @return $this
     */
    public function addDefinitions(bool $overrideDefinitions, iterable $definitions): static;

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
    public function enableCompilation(string $outputDirectory, string $containerClass, int $permissionCompiledContainerFile = 0666, bool $isExclusiveLockFile = true, array $options = []): static;

    /**
     * Build dependency injection container.
     *
     * @throws ContainerBuilderExceptionInterface
     */
    public function build(): DiContainerCallInterface&DiContainerInterface&DiContainerSetterInterface;
}

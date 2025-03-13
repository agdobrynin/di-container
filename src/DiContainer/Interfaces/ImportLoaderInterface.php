<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use InvalidArgumentException;
use Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use RuntimeException;

/**
 * Import classes from a directory.
 *
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
interface ImportLoaderInterface
{
    /**
     * Set finder file.
     */
    public function setFinderFile(FinderFileInterface $finderFile): static;

    /**
     * Set finder fully qualified names in source files.
     */
    public function setFinderFullyQualifiedName(FinderFullyQualifiedNameInterface $finderFullyQualifiedName): static;

    /**
     * Find fully qualified names with a namespace.
     *
     * @param non-empty-string $namespace PSR-4 namespace prefix
     *
     * @throws InvalidArgumentException
     */
    public function setNamespace(string $namespace): static;

    /**
     * Get PSR-4 namespace prefix for find.
     *
     * @return non-empty-string
     *
     * @throws RuntimeException
     */
    public function getNamespace(): string;

    /**
     * Set source directory with filtering parameters.
     *
     * @param non-empty-string       $src                       source directory
     * @param list<non-empty-string> $excludeFilesRegExpPattern exclude files matching by regexp pattern
     * @param list<non-empty-string> $availableExtensions       available files extensions, empty list available all files
     *
     * @throws InvalidArgumentException
     */
    public function setSrc(string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php']): static;

    /**
     * Get fully qualified names from source directory.
     *
     * @return iterable<non-negative-int, ItemFQN>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getFullyQualifiedName(): iterable;
}

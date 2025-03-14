<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use InvalidArgumentException;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use RuntimeException;

/**
 * Import classes from a directory.
 *
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
interface ImportLoaderInterface
{
    public function __clone(): void;

    /**
     * Set source directory with filtering parameters.
     *
     * @param non-empty-string       $src                       source directory
     * @param list<non-empty-string> $excludeFilesRegExpPattern exclude files matching by regexp pattern
     * @param list<non-empty-string> $availableExtensions       available files extensions, empty list available all files
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setSrc(string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php']): static;

    /**
     * Get fully qualified names from source directory.
     *
     * @param non-empty-string $namespace PSR-4 namespace prefix
     *
     * @return iterable<non-negative-int, ItemFQN>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getFullyQualifiedName(string $namespace): iterable;
}

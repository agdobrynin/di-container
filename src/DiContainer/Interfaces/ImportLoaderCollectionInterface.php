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
interface ImportLoaderCollectionInterface
{
    /**
     * Import fully qualified names from directory.
     *
     * @param non-empty-string       $namespace                 PSR-4 namespace prefix
     * @param non-empty-string       $src                       source directory
     * @param list<non-empty-string> $excludeFilesRegExpPattern exclude files matching by regexp pattern
     * @param list<non-empty-string> $availableExtensions       available files extensions, empty list available all files
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function importFromNamespace(string $namespace, string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php']): static;

    /**
     * Get fully qualified names.
     *
     * @return iterable<non-negative-int, array{namespace: non-empty-string, itemFQN: ItemFQN}>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getFullyQualifiedName(): iterable;
}

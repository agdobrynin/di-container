<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

use InvalidArgumentException;
use Iterator;
use RuntimeException;

/**
 * @phpstan-type ItemFQN array{fqn: class-string, tokenId: \T_CLASS | \T_INTERFACE, line: null|int, file: string}
 *
 * Find classes and interfaces in source files.
 */
interface FinderFullyQualifiedNameInterface
{
    /**
     * PSR-4 namespace prefix for class search.
     *
     * @return non-empty-string
     */
    public function getNamespace(): string;

    /**
     * Get configured file finder.
     */
    public function getFinderFile(): FinderFileInterface;

    /**
     * Find fully qualified class names not excluded via file finder.
     *
     * Search using the `\Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface::getFiles()` method.
     *
     * @return Iterator<ItemFQN>
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function getMatched(): Iterator;

    /**
     * Find fully qualified class names excluded via file finder.
     *
     * Search using the `\Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface::getExcludedFiles()` method.
     *
     * @return Iterator<ItemFQN>
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function getExcluded(): Iterator;
}

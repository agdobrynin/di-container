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
     * Get PSR-4 namespace prefix.
     *
     * @return non-empty-string
     */
    public function getNamespace(): string;

    /**
     * Get source directory.
     *
     * @return non-empty-string
     */
    public function getSrc(): string;

    /**
     * Find all fully qualified names for classes and interfaces.
     *
     * @return Iterator<non-negative-int, ItemFQN>
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function find(): Iterator;
}

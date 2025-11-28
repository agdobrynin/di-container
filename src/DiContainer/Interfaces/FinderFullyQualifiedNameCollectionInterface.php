<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use InvalidArgumentException;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;

/**
 * Import classes from a directory.
 *
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
interface FinderFullyQualifiedNameCollectionInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function add(FinderFullyQualifiedNameInterface $finderFullyQualifiedName): static;

    /**
     * @return iterable<non-empty-string, FinderFullyQualifiedNameInterface>
     */
    public function get(): iterable;
}

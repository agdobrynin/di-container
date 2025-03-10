<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

/**
 * @phpstan-type ItemFQN array{fqn: class-string, tokenId: \T_CLASS | \T_INTERFACE, line: null|int, file: string}
 *
 * Find classes and interfaces in source files.
 */
interface FinderFullyQualifiedNameInterface
{
    /**
     * Find all fully qualified names for classes and interfaces.
     *
     * @return \Iterator<non-negative-int, ItemFQN>
     *
     * @throws \RuntimeException
     */
    public function find(): \Iterator;
}

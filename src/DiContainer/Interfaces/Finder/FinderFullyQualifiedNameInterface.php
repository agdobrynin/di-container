<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

/**
 * Find classes and interfaces in source files.
 */
interface FinderFullyQualifiedNameInterface
{
    /**
     * Find all fully qualified names for classes and interfaces.
     *
     * @return \Iterator<non-negative-int, class-string>
     *
     * @throws \RuntimeException
     */
    public function find(): \Iterator;
}

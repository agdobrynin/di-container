<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

use InvalidArgumentException;
use Iterator;
use RuntimeException;
use SplFileInfo;

/**
 * @phpstan-type ItemFQN array{fqn: class-string, tokenId: \T_CLASS | \T_INTERFACE, line: null|int, file: string}
 *
 * Find classes and interfaces in source files.
 */
interface FinderFullyQualifiedNameInterface
{
    /**
     * PSR-4 namespace prefix.
     *
     * @param non-empty-string $namespace
     *
     * @throws InvalidArgumentException
     */
    public function setNamespace(string $namespace): static;

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    public function getNamespace(): string;

    /**
     * Files for parsing.
     *
     * @param iterable<non-negative-int, SplFileInfo> $files
     */
    public function setFiles(iterable $files): static;

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

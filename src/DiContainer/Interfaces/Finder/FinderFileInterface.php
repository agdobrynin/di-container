<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

use InvalidArgumentException;
use Iterator;
use SplFileInfo;

interface FinderFileInterface
{
    /**
     * @return Iterator<non-negative-int, SplFileInfo>
     *
     * @throws InvalidArgumentException
     */
    public function getFiles(): Iterator;

    /**
     * Source directory.
     *
     * @param non-empty-string $src
     *
     * @throws InvalidArgumentException
     */
    public function setSrc(string $src): static;

    /**
     * Get source directory.
     *
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    public function getSrc(): string;

    /**
     * Exclude matching by regexp pattern.
     *
     * @param list<non-empty-string> $excludeRegExpPattern
     */
    public function setExcludeRegExpPattern(array $excludeRegExpPattern): static;

    /**
     * Available file extensions.
     * Empty list available all files.
     *
     * @param list<non-empty-string> $extensions
     */
    public function setAvailableExtensions(array $extensions): static;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

use InvalidArgumentException;
use Iterator;
use SplFileInfo;

interface FinderFileInterface
{
    /**
     * @return Iterator<SplFileInfo>
     *
     * @throws InvalidArgumentException
     */
    public function getFiles(): Iterator;

    /**
     * Get source directory.
     *
     * @return non-empty-string
     */
    public function getSrc(): string;

    /**
     * Getting patterns that exclude files.
     *
     * @return list<non-empty-string>
     */
    public function getExclude(): array;

    /**
     * Get available file extensions.
     * Empty list available all files.
     *
     * @return list<non-empty-string>
     */
    public function getAvailableExtensions(): array;
}

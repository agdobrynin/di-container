<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

interface FinderFileInterface
{
    /**
     * @return iterable<non-negative-int, \SplFileInfo>
     */
    public function getFiles(): iterable;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

interface FinderFileInterface
{
    /**
     * @return \Iterator<non-negative-int, \SplFileInfo>
     */
    public function getFiles(): \Iterator;
}

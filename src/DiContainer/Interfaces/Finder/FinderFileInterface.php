<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

use Iterator;
use SplFileInfo;

interface FinderFileInterface
{
    /**
     * @return Iterator<non-negative-int, SplFileInfo>
     */
    public function getFiles(): Iterator;
}

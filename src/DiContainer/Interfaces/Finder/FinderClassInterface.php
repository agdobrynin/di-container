<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

interface FinderClassInterface
{
    /**
     * @return \Iterator<non-negative-int, class-string>
     *
     * @throws \RuntimeException
     */
    public function getClasses(): \Iterator;
}

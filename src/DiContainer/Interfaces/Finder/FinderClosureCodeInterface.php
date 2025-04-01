<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Finder;

use Closure;
use LogicException;
use RuntimeException;

interface FinderClosureCodeInterface
{
    /**
     * @throws RuntimeException
     * @throws LogicException
     */
    public function getCode(Closure $function): string;
}

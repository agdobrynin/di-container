<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Exceptions;

use Throwable;

interface DefinitionsLoaderExceptionInterface extends Throwable
{
    /**
     * @return array<non-negative-int|string, mixed>
     */
    public function getContext(): array;
}

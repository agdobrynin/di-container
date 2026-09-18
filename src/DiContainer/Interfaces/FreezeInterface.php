<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

/**
 * Makes the object read-only.
 */
interface FreezeInterface
{
    public function freeze(): void;
}

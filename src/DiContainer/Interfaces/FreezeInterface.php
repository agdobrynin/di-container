<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

/**
 * Makes the object read-only.
 */
interface FreezeInterface
{
    /**
     * @return $this
     */
    public function freeze(): static;
}

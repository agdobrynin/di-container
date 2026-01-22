<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

/**
 * Reset an object to its initial state.
 *
 * The reset action should return the object to its original state.
 * Clearing the internal buffers, properties, dependencies to the same state they were in when first used.
 */
interface ResetInterface
{
    public function reset(): void;
}

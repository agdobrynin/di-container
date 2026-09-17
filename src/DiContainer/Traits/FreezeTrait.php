<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

trait FreezeTrait
{
    private bool $isFrozen = false;

    public function freeze(): static
    {
        $this->isFrozen = true;

        return $this;
    }
}

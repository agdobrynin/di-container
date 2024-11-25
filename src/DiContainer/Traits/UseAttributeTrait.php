<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

trait UseAttributeTrait
{
    protected bool $useAttribute = false;

    public function isUseAttribute(): bool
    {
        return $this->useAttribute;
    }

    /**
     * @phan-suppress PhanTypeMismatchReturn
     */
    public function setUseAttribute(?bool $useAttribute): static
    {
        $this->useAttribute = (bool) $useAttribute;

        return $this;
    }
}

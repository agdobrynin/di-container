<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\DiDefinitionException;

trait UseAttributeTrait
{
    protected bool $useAttribute = false;

    public function isUseAttribute(): bool
    {
        return $this->useAttribute ?? throw new DiDefinitionException('Need set $useAttribute. Use method setUseAttribute() in '.__CLASS__.' class.');
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

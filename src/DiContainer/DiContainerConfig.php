<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;

final class DiContainerConfig implements DiContainerConfigInterface
{
    public function __construct(
        private readonly bool $useZeroConfigurationDefinition = true,
        private readonly bool $useAttribute = true,
        private readonly bool $isSingletonServiceDefault = false,
    ) {}

    public function isSingletonServiceDefault(): bool
    {
        return $this->isSingletonServiceDefault;
    }

    public function isUseZeroConfigurationDefinition(): bool
    {
        return $this->useZeroConfigurationDefinition;
    }

    public function isUseAttribute(): bool
    {
        return $this->useAttribute;
    }
}

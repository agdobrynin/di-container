<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

interface DiContainerConfigInterface
{
    public function isSingletonServiceDefault(): bool;

    public function isUseZeroConfigurationDefinition(): bool;

    public function isUseAttribute(): bool;
}

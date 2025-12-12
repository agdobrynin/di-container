<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;

final class DiContainerNullConfig implements DiContainerConfigInterface
{
    public function isSingletonServiceDefault(): bool
    {
        return false;
    }

    public function isUseZeroConfigurationDefinition(): bool
    {
        return false;
    }

    public function isUseAttribute(): bool
    {
        return false;
    }
}

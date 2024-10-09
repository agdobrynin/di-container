<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

interface DiContainerConfigInterface
{
    public function isUseAutowire(): bool;

    public function isSharedServiceDefault(): bool;

    public function isUseZeroConfigurationDefinition(): bool;

    public function getReferenceToContainer(string $value): ?string;

    public function isUseAttribute(): bool;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\DiContainerConfigException;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;

final class DiContainerConfig implements DiContainerConfigInterface
{
    private int $referenceContainerSymbolLength;

    public function __construct(
        private bool $useAutowire = true,
        private bool $useZeroConfigurationDefinition = true,
        private bool $useAttribute = true,
        private bool $isSharedServiceDefault = false,
        private string $referenceContainerSymbol = '@',
    ) {
        '' !== $referenceContainerSymbol || throw new DiContainerConfigException('Reference to container symbol cannot be empty.');

        if (false === $this->useAutowire && $useAttribute) {
            throw new DiContainerConfigException('Cannot use php-attribute without Autowire.');
        }

        $this->referenceContainerSymbolLength = $referenceContainerSymbol ? \strlen($referenceContainerSymbol) : null;
    }

    public function isUseAutowire(): bool
    {
        return $this->useAutowire;
    }

    public function isSharedServiceDefault(): bool
    {
        return $this->isSharedServiceDefault;
    }

    public function isUseZeroConfigurationDefinition(): bool
    {
        return $this->useZeroConfigurationDefinition;
    }

    public function getReferenceToContainer(string $value): ?string
    {
        return \str_starts_with($value, $this->referenceContainerSymbol)
            ? \substr($value, $this->referenceContainerSymbolLength)
            : null;
    }

    public function isUseAttribute(): bool
    {
        return $this->useAttribute;
    }
}

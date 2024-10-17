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
        private bool $isSingletonServiceDefault = false,
        private string $referenceContainerSymbol = '@',
    ) {
        '' !== $referenceContainerSymbol || throw new DiContainerConfigException('Reference to container symbol cannot be empty.');

        if (false === $this->useAutowire && $useAttribute) {
            throw new DiContainerConfigException('Cannot use php-attribute without Autowire.');
        }

        $this->referenceContainerSymbolLength = \strlen($referenceContainerSymbol);
    }

    public function isUseAutowire(): bool
    {
        return $this->useAutowire;
    }

    public function isSingletonServiceDefault(): bool
    {
        return $this->isSingletonServiceDefault;
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

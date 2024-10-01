<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

interface DiContainerConfigInterface
{
    public function isUseZeroConfigurationDefinition(): bool;

    public function getAutowire(): ?AutowiredInterface;

    public function getLinkContainerSymbol(): ?string;

    public function isUseLinkContainerDefinition(): bool;

    public function getKeyFromLinkContainerSymbol(string $value): ?string;

    public function getDelimiterAccessArrayNotationSymbol(): ?string;

    public function isArrayNotationSyntaxSyntax(string $value): bool;

    public function isUseArrayNotationDefinition(): bool;
}

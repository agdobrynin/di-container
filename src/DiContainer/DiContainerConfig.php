<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\DiContainerConfigException;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;

final class DiContainerConfig implements DiContainerConfigInterface
{
    private ?int $linkContainerSymbolLength = null;
    private ?string $accessArrayNotationRegularExpression = null;

    public function __construct(
        private ?string $linkContainerSymbol = '@',
        private ?string $delimiterAccessArrayNotationSymbol = '.',
        private bool $useAutowire = true,
        private bool $useZeroConfigurationDefinition = true,
        private bool $isSharedServiceDefault = false,
        private bool $useAttribute = true,
    ) {
        '' !== $linkContainerSymbol || throw new DiContainerConfigException('Link container symbol cannot be empty.');
        '' !== $delimiterAccessArrayNotationSymbol || throw new DiContainerConfigException('Delimiter access container symbol cannot be empty.');

        if (null !== $linkContainerSymbol && null !== $delimiterAccessArrayNotationSymbol
            && $linkContainerSymbol === $delimiterAccessArrayNotationSymbol) {
            throw new DiContainerConfigException(
                "Delimiters symbols must be different. Got link container symbol [{$linkContainerSymbol}], delimiter level symbol [{$delimiterAccessArrayNotationSymbol}]"
            );
        }

        if (false === $this->useAutowire && $useAttribute) {
            throw new DiContainerConfigException('Cannot use php-attribute without Autowire.');
        }

        $this->linkContainerSymbolLength = $linkContainerSymbol ? \strlen($linkContainerSymbol) : null;

        if (null !== $this->linkContainerSymbolLength
            && null !== $delimiterAccessArrayNotationSymbol) {
            $this->accessArrayNotationRegularExpression = '/^'.\preg_quote($linkContainerSymbol, '/').
                '((?:\w+'.\preg_quote($delimiterAccessArrayNotationSymbol, '/').')+)\w+$/u';
        }
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

    public function getLinkContainerSymbol(): ?string
    {
        return $this->linkContainerSymbol;
    }

    public function isUseLinkContainerDefinition(): bool
    {
        return null !== $this->linkContainerSymbol;
    }

    public function getKeyFromLinkContainerSymbol(string $value): ?string
    {
        return $this->linkContainerSymbol && (\str_starts_with($value, $this->linkContainerSymbol))
            ? \substr($value, $this->linkContainerSymbolLength)
            : null;
    }

    public function getDelimiterAccessArrayNotationSymbol(): ?string
    {
        return $this->delimiterAccessArrayNotationSymbol;
    }

    public function isArrayNotationSyntaxSyntax(string $value): bool
    {
        return $this->accessArrayNotationRegularExpression
            && \preg_match($this->accessArrayNotationRegularExpression, $value);
    }

    public function isUseArrayNotationDefinition(): bool
    {
        return null !== $this->linkContainerSymbol && null !== $this->delimiterAccessArrayNotationSymbol;
    }

    public function isUseAttribute(): bool
    {
        return $this->useAttribute;
    }
}

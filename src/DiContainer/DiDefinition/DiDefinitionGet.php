<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;

final class DiDefinitionGet implements DiDefinitionLinkInterface
{
    private string $validContainerIdentifier;

    /**
     * @param non-empty-string $containerIdentifier
     */
    public function __construct(private string $containerIdentifier) {}

    /**
     * @throws DiDefinitionException
     */
    public function getDefinition(): string
    {
        return $this->validContainerIdentifier ??= '' === \trim($this->containerIdentifier)
            ? throw new DiDefinitionException('Definition identifier must be a non-empty string.')
            : $this->containerIdentifier;
    }
}

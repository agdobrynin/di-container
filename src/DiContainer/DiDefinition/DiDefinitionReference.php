<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;

final class DiDefinitionReference implements DiDefinitionInterface
{
    /**
     * @param non-empty-string $containerIdentifier
     */
    public function __construct(private string $containerIdentifier)
    {
        if ('' === \trim($containerIdentifier)) {
            throw new DiDefinitionException('Definition identifier must be a non-empty string.');
        }
    }

    public function getDefinition(): string
    {
        return $this->containerIdentifier;
    }
}

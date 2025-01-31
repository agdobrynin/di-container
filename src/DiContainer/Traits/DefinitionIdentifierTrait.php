<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;

trait DefinitionIdentifierTrait
{
    /**
     * @phan-suppress PhanPartialTypeMismatchReturn
     *
     * @throws DiDefinitionException
     */
    private function getIdentifier(mixed $identifier, mixed $definition): string
    {
        return match (true) {
            \is_string($identifier) => $identifier,
            \is_string($definition) => $definition,
            $definition instanceof DiDefinitionIdentifierInterface => $definition->getIdentifier(),
            default => throw new DiDefinitionException(
                \sprintf('Definition identifier must be a non-empty string. Definition [%s].', \get_debug_type($definition))
            )
        };
    }

    private function validateIdentifier(string $identifier): void
    {
        if ('' === \trim($identifier)) {
            throw new DiDefinitionException('Definition identifier must be a non-empty string.');
        }
    }
}

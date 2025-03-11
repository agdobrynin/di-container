<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;

use function get_debug_type;
use function is_string;
use function sprintf;
use function trim;

trait DefinitionIdentifierTrait
{
    /**
     * @phpstan-return class-string|non-empty-string
     *
     * @throws DiDefinitionException
     */
    private function getIdentifier(mixed $identifier, mixed $definition): string
    {
        return match (true) { // @phpstan-ignore return.type
            is_string($identifier) && '' !== trim($identifier) => $identifier,
            is_string($definition) && '' !== trim($definition) => $definition,
            $definition instanceof DiDefinitionIdentifierInterface => $definition->getIdentifier(),
            default => throw new DiDefinitionException(
                sprintf('Definition identifier must be a non-empty string. Definition [%s].', get_debug_type($definition))
            )
        };
    }
}

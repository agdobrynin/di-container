<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\ContainerIdentifierException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;

use function is_string;

trait DefinitionIdentifierTrait
{
    /**
     * @phpstan-return class-string|non-empty-string
     *
     * @throws ContainerIdentifierExceptionInterface
     */
    private function getIdentifier(mixed $identifier, mixed $definition): string
    {
        if (is_string($identifier) && '' !== $identifier) {
            return $identifier;
        }

        if ($definition instanceof DiDefinitionIdentifierInterface) {
            return $definition->getIdentifier();
        }

        throw (
            new ContainerIdentifierException(message: 'Definition identifier must be a non-empty string.')
        )
            ->setContext(identifier: $identifier, definition: $definition)
        ;
    }
}

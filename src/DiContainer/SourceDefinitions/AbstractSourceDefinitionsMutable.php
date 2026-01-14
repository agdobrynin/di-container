<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\SourceDefinitionsMutableException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\SourceDefinitionsMutableExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceDefinitionsMutableInterface;

use function array_key_exists;
use function get_debug_type;
use function sprintf;

abstract class AbstractSourceDefinitionsMutable implements SourceDefinitionsMutableInterface
{
    /**
     * @throws ContainerIdentifierExceptionInterface
     */
    public function offsetExists(mixed $offset): bool
    {
        $identifier = Helper::getContainerIdentifier($offset, null);

        return array_key_exists($identifier, $this->definitions());
    }

    /**
     * @throws ContainerIdentifierExceptionInterface
     */
    public function offsetGet(mixed $offset): mixed
    {
        $identifier = Helper::getContainerIdentifier($offset, null);

        return $this->definitions()[$identifier]
            ?? throw new SourceDefinitionsMutableException(
                sprintf('Unregistered the container identifier "%s" in the source.', $identifier)
            );
    }

    /**
     * @throws ContainerIdentifierExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->pushDefinition(...$this->validateDefinition($offset, $value));
    }

    /**
     * @throws ContainerIdentifierExceptionInterface|SourceDefinitionsMutableExceptionInterface
     */
    public function offsetUnset(mixed $offset): void
    {
        $identifier = Helper::getContainerIdentifier($offset, null);

        throw new SourceDefinitionsMutableException(
            sprintf('Definitions in the source are non-removable. Operation using the container identifier "%s".', $identifier)
        );
    }

    /**
     * @return array{0: non-empty-string, 1: mixed}
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface|ContainerIdentifierExceptionInterface
     */
    protected function validateDefinition(mixed $identifier, mixed $definition): array
    {
        $identifier = Helper::getContainerIdentifier($identifier, $definition);

        if (array_key_exists($identifier, $this->definitions())) {
            throw new ContainerAlreadyRegisteredException(
                sprintf('The container identifier "%s" already registered in the source. Definition type: "%s".', $identifier, get_debug_type($definition))
            );
        }

        return [$identifier, $definition];
    }

    /**
     * @return array<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed>
     */
    abstract protected function definitions(): array;

    abstract protected function pushDefinition(mixed $offset, mixed $value): void;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Closure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerIdentifierException;
use Kaspi\DiContainer\Exception\SourceDefinitionsMutableException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\SourceDefinitionsMutableExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceDefinitionsMutableInterface;
use Traversable;

use function get_debug_type;
use function is_string;
use function sprintf;
use function var_export;

abstract class AbstractSourceDefinitionsMutable implements SourceDefinitionsMutableInterface
{
    public function getIterator(): Traversable
    {
        yield from $this->definitions();
    }

    public function offsetExists(mixed $offset): bool
    {
        if (is_string($offset) && '' !== $offset) {
            return isset($this->definitions()[$offset]);
        }

        return false;
    }

    /**
     * @throws SourceDefinitionsMutableExceptionInterface
     */
    public function offsetGet(mixed $offset): DiDefinitionInterface
    {
        if (!$this->offsetExists($offset)) {
            $message = is_string($offset)
                ? sprintf('Unregistered the container identifier "%s" in the source.', $offset)
                : sprintf('Unsupported identifier type "%s"', get_debug_type($offset));

            throw new SourceDefinitionsMutableException($message);
        }

        return $this->definitions()[$offset]; // @phpstan-ignore offsetAccess.invalidOffset
    }

    /**
     * @throws ContainerIdentifierExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $identifier = match (true) {
            is_string($offset) && '' !== $offset => $offset,
            $value instanceof DiDefinitionIdentifierInterface => $value->getIdentifier(),
            default => throw new ContainerIdentifierException(
                sprintf('Definition identifier must be a non-empty string. Definition type "%s".', get_debug_type($value))
            )
        };

        if ($this->offsetExists($identifier)) {
            throw new ContainerAlreadyRegisteredException(
                sprintf('The container identifier "%s" already registered in the source. Definition type: "%s".', $identifier, get_debug_type($value))
            );
        }

        $definition = match (true) {
            $value instanceof DiDefinitionInterface => $value,
            $value instanceof Closure => new DiDefinitionCallable($value),
            default => new DiDefinitionValue($value)
        };

        $this->definitions()[$identifier] = $definition;
        unset($this->removedDefinitionIds()[$identifier]);
    }

    /**
     * @throws ContainerIdentifierExceptionInterface
     * @throws SourceDefinitionsMutableExceptionInterface
     */
    public function offsetUnset(mixed $offset): void
    {
        $identifier = is_string($offset)
            ? $offset
            : var_export($offset, true);

        throw new SourceDefinitionsMutableException(
            sprintf('Definitions in the source are non-removable. Operation using the container identifier "%s".', $identifier)
        );
    }

    public function getRemovedDefinitionIds(): iterable
    {
        return $this->removedDefinitionIds();
    }

    /**
     * @return array<non-empty-string, DiDefinitionInterface>
     */
    abstract protected function &definitions(): array;

    /**
     * @return array<class-string|non-empty-string, true>
     */
    abstract protected function &removedDefinitionIds(): array;
}

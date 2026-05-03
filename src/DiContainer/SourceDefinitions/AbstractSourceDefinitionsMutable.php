<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Closure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Exception\SourceDefinitionsMutableException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionRuntimeInterface;
use Kaspi\DiContainer\Interfaces\SourceDefinitionsMutableInterface;
use Traversable;

use function get_debug_type;
use function is_object;
use function sprintf;
use function var_export;

abstract class AbstractSourceDefinitionsMutable implements SourceDefinitionsMutableInterface
{
    public function getIterator(): Traversable
    {
        yield from $this->definitions();
    }

    public function has(string $id): bool
    {
        return !('' === $id) && isset($this->definitions()[$id]);
    }

    public function get(string $id): DiDefinitionInterface
    {
        return $this->definitions()[$id] ?? throw new SourceDefinitionsMutableException(
            sprintf('Unregistered the container identifier %s in the source.', var_export($id, true))
        );
    }

    public function set(int|string $id, mixed $value): void
    {
        $identifier = Helper::getContainerIdentifier($id, $value);

        if ($this->has($identifier)) {
            $definition = $this->definitions()[$identifier];

            if (!$definition instanceof DiDefinitionRuntimeInterface) {
                throw new ContainerAlreadyRegisteredException(
                    sprintf('Definition type: "%s".', get_debug_type($value)),
                    id: $identifier,
                );
            }

            if (!is_object($value)) {
                throw new DiDefinitionException(
                    sprintf('The runtime definition with the identifier %s must be specified as an object. Got value type "%s".', var_export($identifier, true), get_debug_type($value))
                );
            }

            $definition->setDefinition($value);

            return;
        }

        $this->definitions()[$identifier] = match (true) {
            $value instanceof DiDefinitionInterface => $value,
            $value instanceof Closure => new DiDefinitionCallable($value),
            default => new DiDefinitionValue($value)
        };

        unset($this->removedDefinitionIds()[$identifier]);
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

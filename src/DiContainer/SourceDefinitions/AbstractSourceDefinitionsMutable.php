<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
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
        foreach ($this->initializerDefinitions() as $item) {
            yield $item->containerIdentifier => $item->diDefinition;
        }
    }

    public function has(string $id): bool
    {
        return isset($this->initializerDefinitions()[$id]);
    }

    public function get(string $id): ?DiDefinitionInterface
    {
        return ($this->initializerDefinitions()[$id] ?? null)?->diDefinition;
    }

    public function set(int|string $id, mixed $value): void
    {
        $item = new SourceDefinitionItem($id, $value, true);
        $definition = $this->get($item->containerIdentifier);

        if (null !== $definition) {
            if (!$definition instanceof DiDefinitionRuntimeInterface) {
                throw new ContainerAlreadyRegisteredException(
                    sprintf('Definition type: "%s".', get_debug_type($value)),
                    id: $item->containerIdentifier,
                );
            }

            if (!is_object($value)) {
                throw new DiDefinitionException(
                    sprintf('The runtime definition with the identifier %s must be specified as an object. Got value type "%s".', var_export($item->containerIdentifier, true), get_debug_type($value))
                );
            }

            $definition->setDefinition($value);

            return;
        }

        $this->initializerDefinitions()[$item->containerIdentifier] = $item;
        $item->isReplaceRemovedId = $this->isRemovedDefinition($item->containerIdentifier);
        unset($this->initializerRemovedIds()[$item->containerIdentifier]);
    }

    public function getRemovedDefinitionIds(): iterable
    {
        return $this->initializerRemovedIds();
    }

    /**
     * @return array<non-empty-string, SourceDefinitionItem>
     */
    abstract protected function &initializerDefinitions(): array;

    /**
     * @return array<class-string|non-empty-string, true>
     */
    abstract protected function &initializerRemovedIds(): array;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Closure;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\SourceDefinitionsMutableException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\SourceDefinitionsMutableExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceDefinitionsMutableInterface;
use Traversable;

use function array_key_exists;
use function get_debug_type;
use function sprintf;

final class SourceDefinitionsMutable implements SourceDefinitionsMutableInterface
{
    /** @var array<class-string|non-empty-string, mixed> */
    private array $definitions;

    /** @var Closure(): array<class-string|non-empty-string, mixed> */
    private Closure $initDefinitions;

    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $sourceDefinitions
     */
    public function __construct(private iterable $sourceDefinitions)
    {
        $this->initDefinitions = function () {
            if (isset($this->definitions)) {
                return $this->definitions;
            }

            $this->definitions = [];
            foreach ($this->sourceDefinitions as $identifier => $sourceDefinition) {
                $this->definitions += $this->validateDefinition($identifier, $sourceDefinition);
            }

            unset($this->sourceDefinitions);

            return $this->definitions;
        };
    }

    /**
     * @throws ContainerIdentifierExceptionInterface
     */
    public function offsetExists(mixed $offset): bool
    {
        $identifier = Helper::getContainerIdentifier($offset, null);

        return array_key_exists($identifier, $this->definitions ?? ($this->initDefinitions)());
    }

    /**
     * @throws ContainerIdentifierExceptionInterface
     */
    public function offsetGet(mixed $offset): mixed
    {
        $identifier = Helper::getContainerIdentifier($offset, null);

        return $this->definitions[$identifier]
            ?? ($this->initDefinitions)()[$identifier]
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
        ($this->initDefinitions)();

        $this->definitions += $this->validateDefinition($offset, $value);
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

    public function getIterator(): Traversable
    {
        yield from ($this->initDefinitions)();
    }

    /**
     * @return non-empty-array<non-empty-string, mixed>
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface|ContainerIdentifierExceptionInterface
     */
    private function validateDefinition(mixed $identifier, mixed $definition): array
    {
        $identifier = Helper::getContainerIdentifier($identifier, $definition);

        if (array_key_exists($identifier, $this->definitions ?? ($this->initDefinitions)())) {
            throw new ContainerAlreadyRegisteredException(
                sprintf('The container identifier "%s" already registered in the source. Definition type: "%s".', $identifier, get_debug_type($definition))
            );
        }

        return [$identifier => $definition];
    }
}

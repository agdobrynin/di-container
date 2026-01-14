<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Traversable;

use function reset;

final class ImmediateSourceDefinitionsMutable extends AbstractSourceDefinitionsMutable
{
    /** @var array<class-string|non-empty-string, mixed> */
    private array $definitions;

    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $sourceDefinitions
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface|ContainerIdentifierExceptionInterface
     */
    public function __construct(private iterable $sourceDefinitions)
    {
        $this->definitions = [];

        foreach ($this->sourceDefinitions as $identifier => $sourceDefinition) {
            $this->pushDefinition($identifier, $sourceDefinition);
        }

        unset($this->sourceDefinitions);
    }

    public function getIterator(): Traversable
    {
        reset($this->definitions);

        yield from $this->definitions;
    }

    protected function definitions(): array
    {
        return $this->definitions;
    }

    /**
     * @throws ContainerIdentifierExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    protected function pushDefinition(mixed $offset, mixed $value): void
    {
        [$identifier, $definition] = $this->validateDefinition($offset, $value);
        $this->definitions[$identifier] = $definition;
    }
}

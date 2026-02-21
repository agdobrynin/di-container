<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;

final class ImmediateSourceDefinitionsMutable extends AbstractSourceDefinitionsMutable
{
    /** @var array<class-string|non-empty-string, DiDefinitionInterface> */
    private array $definitions;

    /** @var array<class-string|non-empty-string, true> */
    private array $removedDefinitionIds;

    /**
     * @param iterable<non-empty-string|non-negative-int, mixed> $sourceDefinitions
     * @param iterable<class-string|non-empty-string, mixed>     $sourceRemovedDefinitionIds
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface|ContainerIdentifierExceptionInterface
     */
    public function __construct(iterable $sourceDefinitions, iterable $sourceRemovedDefinitionIds = [])
    {
        $this->definitions = [];
        $this->removedDefinitionIds = [];

        foreach ($sourceDefinitions as $identifier => $sourceDefinition) {
            $this->offsetSet($identifier, $sourceDefinition);
        }

        foreach ($sourceRemovedDefinitionIds as $identifier => $v) {
            $this->removedDefinitionIds[$identifier] = true;

            if (isset($this->definitions[$identifier])) {
                unset($this->definitions[$identifier]);
            }
        }
    }

    public function isRemovedDefinition(string $id): bool
    {
        return isset($this->removedDefinitionIds[$id]);
    }

    protected function &definitions(): array
    {
        return $this->definitions;
    }

    protected function &removedDefinitionIds(): array
    {
        return $this->removedDefinitionIds;
    }
}

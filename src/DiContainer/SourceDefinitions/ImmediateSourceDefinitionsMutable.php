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

    /**
     * @param iterable<non-empty-string|non-negative-int, mixed> $sourceDefinitions
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface|ContainerIdentifierExceptionInterface
     */
    public function __construct(private iterable $sourceDefinitions)
    {
        $this->definitions = [];

        foreach ($this->sourceDefinitions as $identifier => $sourceDefinition) {
            $this->offsetSet($identifier, $sourceDefinition);
        }

        unset($this->sourceDefinitions);
    }

    protected function &definitions(): array
    {
        return $this->definitions;
    }
}

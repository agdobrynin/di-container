<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;

use function get_debug_type;
use function sprintf;

final class ImmediateSourceDefinitionsMutable extends AbstractSourceDefinitionsMutable
{
    /** @var array<class-string|non-empty-string, SourceDefinitionItem> */
    private array $definitions;

    /** @var array<class-string|non-empty-string, true> */
    private array $removedDefinitionIds;

    /**
     * @param iterable<non-empty-string|non-negative-int, mixed> $sourceDefinitions
     * @param iterable<class-string|non-empty-string, mixed>     $sourceRemovedDefinitionIds
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface
     * @throws ContainerIdentifierExceptionInterface
     */
    public function __construct(iterable $sourceDefinitions, iterable $sourceRemovedDefinitionIds = [])
    {
        $this->definitions = [];
        $this->removedDefinitionIds = [];

        foreach ($sourceDefinitions as $identifier => $sourceDefinition) {
            $item = new SourceDefinitionItem($identifier, $sourceDefinition, false);

            if (isset($this->definitions[$item->containerIdentifier])) {
                throw new ContainerAlreadyRegisteredException(
                    sprintf('Definition type: "%s".', get_debug_type($sourceDefinition)),
                    id: $item->containerIdentifier,
                );
            }

            $this->definitions[$item->containerIdentifier] = $item;
        }

        foreach ($sourceRemovedDefinitionIds as $identifier => $v) {
            $this->removedDefinitionIds[$identifier] = true;
            unset($this->definitions[$identifier]);
        }
    }

    public function isRemovedDefinition(string $id): bool
    {
        return isset($this->removedDefinitionIds[$id]);
    }

    protected function &initializerDefinitions(): array
    {
        return $this->definitions;
    }

    protected function &initializerRemovedIds(): array
    {
        return $this->removedDefinitionIds;
    }
}

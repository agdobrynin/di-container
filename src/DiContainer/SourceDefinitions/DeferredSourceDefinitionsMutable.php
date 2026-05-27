<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Closure;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;

use function get_debug_type;
use function sprintf;

final class DeferredSourceDefinitionsMutable extends AbstractSourceDefinitionsMutable
{
    /** @var ?Closure(): iterable<(class-string|non-empty-string|non-negative-int), mixed> */
    private ?Closure $sourceDefinitions;

    /** @var null|Closure(): iterable<(class-string|non-empty-string), mixed> */
    private ?Closure $sourceRemovedDefinitionIds;

    /** @var array<class-string|non-empty-string, SourceDefinitionItem> */
    private array $definitions;

    /** @var array<class-string|non-empty-string, true> */
    private array $removedDefinitionIds;

    /**
     * @param callable(): iterable<(class-string|non-empty-string|non-negative-int), mixed> $sourceDefinitions
     * @param null|callable(): iterable<(class-string|non-empty-string), mixed>             $sourceRemovedDefinitionIds
     */
    public function __construct(callable $sourceDefinitions, ?callable $sourceRemovedDefinitionIds = null)
    {
        $this->sourceDefinitions = $sourceDefinitions(...);
        $this->sourceRemovedDefinitionIds = null !== $sourceRemovedDefinitionIds
            ? $sourceRemovedDefinitionIds(...)
            : null;
    }

    public function isRemovedDefinition(string $id): bool
    {
        if (!isset($this->removedDefinitionIds)) {
            $this->initializerDefinitions();
        }

        return isset($this->removedDefinitionIds[$id]);
    }

    protected function &initializerDefinitions(): array
    {
        if (null !== $this->sourceDefinitions) {
            $this->definitions = [];
            $this->removedDefinitionIds = [];

            foreach (($this->sourceDefinitions)() as $identifier => $sourceDefinition) {
                $item = new SourceDefinitionItem($identifier, $sourceDefinition, false);

                if (isset($this->definitions[$item->containerIdentifier])) {
                    throw new ContainerAlreadyRegisteredException(
                        sprintf('Definition type: "%s".', get_debug_type($sourceDefinition)),
                        id: $item->containerIdentifier,
                    );
                }

                $this->definitions[$item->containerIdentifier] = $item;
            }

            if (null !== $this->sourceRemovedDefinitionIds) {
                foreach (($this->sourceRemovedDefinitionIds)() as $identifier => $v) {
                    $this->removedDefinitionIds[$identifier] = true;
                    unset($this->definitions[$identifier]);
                }
            }

            $this->sourceDefinitions = $this->sourceRemovedDefinitionIds = null;
        }

        return $this->definitions;
    }

    protected function &initializerRemovedIds(): array
    {
        if (null !== $this->sourceDefinitions) {
            $this->initializerDefinitions();
        }

        return $this->removedDefinitionIds;
    }
}

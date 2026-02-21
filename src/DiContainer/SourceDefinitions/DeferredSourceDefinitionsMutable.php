<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Closure;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;

final class DeferredSourceDefinitionsMutable extends AbstractSourceDefinitionsMutable
{
    /** @var array<class-string|non-empty-string, DiDefinitionInterface> */
    private array $definitions;

    /** @var array<class-string|non-empty-string, true> */
    private array $removedDefinitionIds;

    /**
     * @param Closure(): iterable<non-empty-string|non-negative-int, mixed>  $sourceDefinitions
     * @param null|Closure(): iterable<class-string|non-empty-string, mixed> $sourceRemovedDefinitionIds
     */
    public function __construct(private Closure $sourceDefinitions, private ?Closure $sourceRemovedDefinitionIds = null) {}

    public function isRemovedDefinition(string $id): bool
    {
        if (!isset($this->removedDefinitionIds)) {
            $this->definitions();
        }

        return isset($this->removedDefinitionIds[$id]);
    }

    protected function &definitions(): array
    {
        if (!isset($this->definitions)) {
            $this->definitions = [];
            $this->removedDefinitionIds = [];

            foreach (($this->sourceDefinitions)() as $identifier => $sourceDefinition) {
                $this->offsetSet($identifier, $sourceDefinition);
            }

            if (null !== $this->sourceRemovedDefinitionIds) {
                foreach (($this->sourceRemovedDefinitionIds)() as $identifier => $v) {
                    $this->removedDefinitionIds[$identifier] = true;

                    if (isset($this->definitions[$identifier])) {
                        unset($this->definitions[$identifier]);
                    }
                }
            }

            unset($this->sourceDefinitions, $this->sourceRemovedDefinitionIds);
        }

        return $this->definitions;
    }

    protected function &removedDefinitionIds(): array
    {
        if (!isset($this->definitions)) {
            $this->definitions();
        }

        return $this->removedDefinitionIds;
    }
}

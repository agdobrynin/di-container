<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;

final class DeferredSourceDefinitionsMutable extends AbstractSourceDefinitionsMutable
{
    /** @var callable(): iterable<(class-string|non-empty-string|non-negative-int), mixed> */
    private $sourceDefinitions;

    /** @var null|callable(): iterable<(class-string|non-empty-string), mixed> */
    private $sourceRemovedDefinitionIds;

    /** @var array<class-string|non-empty-string, DiDefinitionInterface> */
    private array $definitions;

    /** @var array<class-string|non-empty-string, true> */
    private array $removedDefinitionIds;

    /**
     * @param callable(): iterable<(class-string|non-empty-string|non-negative-int), mixed> $sourceDefinitions
     * @param null|callable(): iterable<(class-string|non-empty-string), mixed>             $sourceRemovedDefinitionIds
     */
    public function __construct(callable $sourceDefinitions, ?callable $sourceRemovedDefinitionIds = null)
    {
        $this->sourceDefinitions = $sourceDefinitions;
        $this->sourceRemovedDefinitionIds = $sourceRemovedDefinitionIds;
    }

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
                $this->set($identifier, $sourceDefinition);
            }

            if (null !== $this->sourceRemovedDefinitionIds) {
                foreach (($this->sourceRemovedDefinitionIds)() as $identifier => $v) {
                    $this->removedDefinitionIds[$identifier] = true;
                    unset($this->definitions[$identifier]); // @phpstan-ignore unset.offset
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

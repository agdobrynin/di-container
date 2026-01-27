<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;

final class DeferredSourceDefinitionsMutable extends AbstractSourceDefinitionsMutable
{
    /** @var array<class-string|non-empty-string, DiDefinitionInterface> */
    private array $definitions;

    /**
     * @param iterable<non-empty-string|non-negative-int, mixed> $sourceDefinitions
     */
    public function __construct(private iterable $sourceDefinitions) {}

    protected function &definitions(): array
    {
        if (!isset($this->definitions)) {
            $this->definitions = [];

            foreach ($this->sourceDefinitions as $identifier => $sourceDefinition) {
                $this->offsetSet($identifier, $sourceDefinition);
            }

            unset($this->sourceDefinitions);
        }

        return $this->definitions;
    }
}

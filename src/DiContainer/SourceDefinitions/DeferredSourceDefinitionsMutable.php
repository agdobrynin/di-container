<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\SourceDefinitions;

use Closure;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Traversable;

final class DeferredSourceDefinitionsMutable extends AbstractSourceDefinitionsMutable
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
        $this->initDefinitions = function (): array {
            if (isset($this->definitions)) {
                return $this->definitions;
            }

            $this->definitions = [];

            foreach ($this->sourceDefinitions as $identifier => $sourceDefinition) {
                $this->pushDefinition($identifier, $sourceDefinition);
            }

            unset($this->sourceDefinitions);

            return $this->definitions;
        };
    }

    public function getIterator(): Traversable
    {
        yield from ($this->initDefinitions)();
    }

    protected function definitions(): array
    {
        return ($this->initDefinitions)();
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

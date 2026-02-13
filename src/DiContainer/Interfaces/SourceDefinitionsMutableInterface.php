<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use ArrayAccess;
use IteratorAggregate;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Traversable;

interface SourceDefinitionsMutableInterface extends ArrayAccess, IteratorAggregate
{
    /**
     * @return Traversable<non-empty-string, DiDefinitionInterface>
     */
    public function getIterator(): Traversable;

    /**
     * @param non-empty-string $id
     */
    public function isRemovedDefinitionId(string $id): bool;
}

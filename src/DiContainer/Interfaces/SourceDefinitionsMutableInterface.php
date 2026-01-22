<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use ArrayAccess;
use IteratorAggregate;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Traversable;

interface SourceDefinitionsMutableInterface extends ArrayAccess, IteratorAggregate
{
    /**
     * @return Traversable<non-empty-string, DiDefinitionIdentifierInterface|mixed>
     */
    public function getIterator(): Traversable;
}

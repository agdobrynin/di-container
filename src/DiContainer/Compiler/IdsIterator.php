<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Interfaces\Compiler\IdsIteratorInterface;

use function current;
use function key;
use function next;
use function reset;

final class IdsIterator implements IdsIteratorInterface
{
    /** @var array<non-empty-string, non-empty-string> */
    private array $ids = [];

    public function valid(): bool
    {
        return null !== $this->key();
    }

    public function rewind(): void
    {
        reset($this->ids);
    }

    public function current(): false|string
    {
        return current($this->ids);
    }

    public function next(): void
    {
        next($this->ids);
    }

    public function key(): ?string
    {
        return key($this->ids);
    }

    public function add(string $id): void
    {
        $this->ids[$id] = $id;
    }
}

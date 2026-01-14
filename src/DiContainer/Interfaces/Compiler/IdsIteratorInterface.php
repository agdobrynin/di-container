<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Iterator;
use Kaspi\DiContainer\Interfaces\ResetInterface;

interface IdsIteratorInterface extends ResetInterface, Iterator
{
    /**
     * @return false|non-empty-string
     */
    public function current(): false|string;

    public function next(): void;

    public function key(): ?string;

    /**
     * @param non-empty-string $id
     */
    public function add(string $id): void;
}

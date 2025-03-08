<?php

declare(strict_types=1);

namespace Tests\FinderFullyQualifiedClassName\Fixtures\Success;

abstract // My super fantastic code style 😁

/**
 * Hmmm 🙄.
 */

// Uff 😎
class OneAbstract
{
    private array $items = [];

    public function __construct(private string $needle) {}

    abstract public function push(string $item): void;

    public function getStrings(): array
    {
        return $this->items;
    }
}

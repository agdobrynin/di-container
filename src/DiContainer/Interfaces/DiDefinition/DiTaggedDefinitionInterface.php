<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiTaggedDefinitionInterface
{
    /**
     * Get bound tags with options.
     *
     * @todo Add doc block.
     *
     * @return array<non-empty-string, array<non-empty-string, mixed>>
     */
    public function getTags(): array;

    /**
     * Get tag options.
     *
     * @param non-empty-string $name
     */
    public function getTag(string $name): ?array;

    /**
     * Has tag.
     *
     * @param non-empty-string $name
     */
    public function hasTag(string $name): bool;

    /**
     * Get priority option for tag.
     *
     * @param non-empty-string $name
     */
    public function getOptionPriority(string $name): int;
}

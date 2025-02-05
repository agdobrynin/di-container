<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiTaggedDefinitionInterface
{
    /**
     * Get bound tags with options.
     *
     * @return array<non-empty-string, array<non-empty-string, mixed>>
     */
    public function getTags(): array;

    /**
     * Get tag options.
     *
     * @param non-empty-string $name
     *
     * @return null|array<non-empty-string, mixed>
     */
    public function getTag(string $name): ?array;

    /**
     * Has tag.
     *
     * @param non-empty-string $name
     */
    public function hasTag(string $name): bool;

    /**
     * Get priority for tag.
     *
     * @param non-empty-string $name
     */
    public function geTagPriority(string $name): ?int;
}

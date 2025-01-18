<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiTaggedDefinitionInterface
{
    /**
     * Get bound tags.
     *
     * @todo Add doc block.
     *
     * @return array<non-empty-string, array>
     */
    public function getTags(): array;

    /**
     * Get tag options.
     *
     * @todo Add doc block.
     */
    public function getTag(string $name): ?array;
}

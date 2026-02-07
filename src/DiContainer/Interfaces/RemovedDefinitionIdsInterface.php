<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

interface RemovedDefinitionIdsInterface
{
    /**
     * Get container identifiers for definitions removed from container.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function getRemovedDefinitionIds(): array;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

interface DataFromDefinitionsConfiguratorInterface
{
    /**
     * Get container identifiers for definitions removed via `self::removeDefinition()`.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function getRemovedDefinitionIds(): array;

    /**
     * Get container identifiers for definitions set via `self::forceSetDefinition()`.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function getSetDefinitionIds(): array;
}

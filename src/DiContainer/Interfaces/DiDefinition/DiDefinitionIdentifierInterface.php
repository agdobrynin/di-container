<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionIdentifierInterface
{
    /**
     * @return class-string|non-empty-string
     */
    public function getIdentifier(): string;
}

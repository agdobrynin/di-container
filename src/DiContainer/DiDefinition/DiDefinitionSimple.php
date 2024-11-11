<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;

final class DiDefinitionSimple implements DiDefinitionInterface
{
    public function __construct(private mixed $definition) {}

    public function getDefinition(): mixed
    {
        return $this->definition;
    }
}

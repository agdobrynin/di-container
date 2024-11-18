<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;

final class DiDefinitionValue implements DiDefinitionInterface
{
    public function __construct(private mixed $definition) {}

    public function getDefinition(): mixed
    {
        return $this->definition;
    }
}

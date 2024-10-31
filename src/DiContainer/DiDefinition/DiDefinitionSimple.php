<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiDefinitionInterface;

final class DiDefinitionSimple implements DiDefinitionInterface
{
    public function __construct(private string $id, private mixed $definition) {}

    public function getContainerId(): string
    {
        return $this->id;
    }

    public function getDefinition(): mixed
    {
        return $this->definition;
    }
}

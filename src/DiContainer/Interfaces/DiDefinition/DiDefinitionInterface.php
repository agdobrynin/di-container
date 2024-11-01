<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionInterface
{
    public function getContainerId(): string;

    public function getDefinition(): mixed;
}

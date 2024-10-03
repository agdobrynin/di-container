<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

class DiContainerDefinition
{
    public function __construct(public string $id, public mixed $definition, public bool $shared, public array $arguments = []) {}
}

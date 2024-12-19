<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionSetupInterface extends DiDefinitionArgumentsInterface
{
    public function setup(string $method, mixed ...$argument): self;
}

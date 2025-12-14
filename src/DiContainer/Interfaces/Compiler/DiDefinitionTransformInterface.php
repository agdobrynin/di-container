<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;

interface DiDefinitionTransformInterface
{
    public function transform(DiDefinitionInterface $definition): CompilableDefinitionInterface;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

interface DiDefinitionTransformInterface
{
    public function transform(mixed $definition): CompilableDefinitionInterface;
}

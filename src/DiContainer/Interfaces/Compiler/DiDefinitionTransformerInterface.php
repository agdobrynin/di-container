<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

interface DiDefinitionTransformerInterface
{
    public function transform(mixed $definition): CompilableDefinitionInterface;
}

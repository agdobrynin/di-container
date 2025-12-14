<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;

interface DiDefinitionTransformerInterface
{
    public function transform(mixed $definition, DiContainerInterface $container): CompilableDefinitionInterface;
}

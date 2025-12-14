<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformInterface;

final class DiDefinitionTransform implements DiDefinitionTransformInterface
{
    public function transform(mixed $definition): CompilableDefinitionInterface
    {
        // TODO: Implement transform() method.
    }
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;

interface DiDefinitionTransformerInterface
{
    public function transform(mixed $definition, DiContainerInterface $container): CompilableDefinitionInterface;

    public function getClosureParser(): FinderClosureCodeInterface;
}

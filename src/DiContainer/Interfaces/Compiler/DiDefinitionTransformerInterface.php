<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Closure;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;

interface DiDefinitionTransformerInterface
{
    public function getClosureParser(): FinderClosureCodeInterface;

    /**
     * @param null|Closure(mixed $definition, DiContainerInterface $container): CompilableDefinitionInterface $fallback
     *
     * @throws DefinitionCompileException
     */
    public function transform(mixed $definition, DiContainerInterface $container, ?Closure $fallback = null): CompilableDefinitionInterface;
}

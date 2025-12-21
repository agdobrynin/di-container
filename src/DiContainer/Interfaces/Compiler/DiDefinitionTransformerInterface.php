<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Closure;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;

interface DiDefinitionTransformerInterface
{
    public function getClosureParser(): FinderClosureCodeInterface;

    /**
     * @param null|Closure(mixed $definition, DiContainerDefinitionsInterface $diContainerDefinitions): CompilableDefinitionInterface $fallback
     *
     * @throws DefinitionCompileException
     */
    public function transform(mixed $definition, DiContainerDefinitionsInterface $diContainerDefinitions, ?Closure $fallback = null): CompilableDefinitionInterface;
}

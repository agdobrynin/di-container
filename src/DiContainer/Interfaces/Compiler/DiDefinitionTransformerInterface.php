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
     * @param null|Closure(mixed $definition, DiContainerDefinitionsInterface $containerDefinitionIterator): CompilableDefinitionInterface $fallback
     *
     * @throws DefinitionCompileException
     */
    public function transform(mixed $definition, DiContainerDefinitionsInterface $containerDefinitionIterator, ?Closure $fallback = null): CompilableDefinitionInterface;
}

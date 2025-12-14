<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;

interface CompilableDefinitionInterface
{
    /**
     * @param non-empty-string       $containerVariableName variable name for access to current di-container instance aka `$this` or `$this->container` and etc
     * @param list<non-empty-string> $scopeVariableNames    list of variables witch help generate container entity object into parameter `$scopeServiceVariableName`
     * @param mixed                  $context               some context for compile definition
     *
     * @throws DefinitionCompileExceptionInterface
     */
    public function compile(string $containerVariableName, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface;

    /**
     * @return DiDefinitionArgumentsInterface|DiDefinitionInterface|DiDefinitionLinkInterface|DiDefinitionSetupAutowireInterface|DiDefinitionSingletonInterface|DiDefinitionTaggedAsInterface|mixed
     */
    public function getDiDefinition(): mixed;
}

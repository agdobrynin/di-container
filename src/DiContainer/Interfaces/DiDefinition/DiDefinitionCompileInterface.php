<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCompileExceptionInterface;

interface DiDefinitionCompileInterface
{
    /**
     * @param non-empty-string       $containerVariableName variable name for access to current di-container instance aka `$this` or `$this->container` and etc
     * @param list<non-empty-string> $scopeVariableNames    list of variables witch help generate container entity object into parameter `$scopeServiceVariableName`
     * @param mixed                  $context               some context for compile definition
     *
     * @throws DiDefinitionCompileExceptionInterface
     */
    public function compile(string $containerVariableName, DiContainerInterface $container, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface;
}

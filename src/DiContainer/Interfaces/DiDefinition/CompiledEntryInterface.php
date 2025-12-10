<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface CompiledEntryInterface
{
    public function getExpression(): string;

    public function getStatements(): string;

    public function getScopeVariables(): array;

    public function isSingleton(): bool;

    public function getReturnType(): string;
}

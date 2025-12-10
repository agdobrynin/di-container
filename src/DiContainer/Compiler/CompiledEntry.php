<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Interfaces\DiDefinition\CompiledEntryInterface;

final class CompiledEntry implements CompiledEntryInterface
{
    /**
     * @param list<non-empty-string> $scopeVariables
     * @param non-empty-string       $returnType
     */
    public function __construct(
        private readonly string $expression,
        private readonly string $statements,
        private readonly array $scopeVariables,
        private readonly ?bool $isSingleton,
        private readonly string $returnType = 'mixed',
    ) {}

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function getStatements(): string
    {
        return $this->statements;
    }

    public function getScopeVariables(): array
    {
        return $this->scopeVariables;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }
}

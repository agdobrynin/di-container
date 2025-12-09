<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

final class CompiledEntry
{
    public function __construct(
        private readonly string $expression,
        private readonly array $statements,
        private readonly array $usesVariables,
        private readonly string $returnType = 'mixed',
    ) {}

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function getStatements(): array
    {
        return $this->statements;
    }

    public function getUseVariables(): array
    {
        return $this->usesVariables;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }
}

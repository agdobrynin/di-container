<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;

use function in_array;
use function ltrim;
use function preg_match;
use function sprintf;

final class CompiledEntry implements CompiledEntryInterface
{
    private readonly string $scopeServiceVar;

    /**
     * @param non-empty-string       $scopeServiceVar
     * @param list<non-empty-string> $scopeVars
     * @param list<non-empty-string> $statements
     * @param non-empty-string       $returnType
     *
     * @throws DefinitionCompileExceptionInterface
     */
    public function __construct(
        string $scopeServiceVar = '$object',
        private ?bool $isSingleton = null,
        private string $expression = '',
        private array $statements = [],
        private array $scopeVars = [],
        private string $returnType = 'mixed',
    ) {
        $this->validateVar($scopeServiceVar, 'Invalid scope service variable.');

        foreach ($scopeVars as $var) {
            $this->validateVar($var, 'Invalid scope variables.');

            if (!in_array($var, $this->scopeVars, true)) {
                $this->scopeVars[] = $var;
            }
        }

        $scopeServiceVarUnique = null;
        $suffixVar = 0;

        while (in_array($scopeServiceVarUnique ?? $scopeServiceVar, $this->scopeVars, true)) {
            ++$suffixVar;
            $scopeServiceVarUnique = $scopeServiceVar.$suffixVar;
        }

        $this->scopeServiceVar = $scopeServiceVarUnique ?? $scopeServiceVar;
        $this->scopeVars[] = $this->scopeServiceVar;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function setExpression(string $expression): static
    {
        $this->expression = $expression;

        return $this;
    }

    public function getStatements(): array
    {
        return $this->statements;
    }

    public function addToStatements(string ...$expression): static
    {
        foreach ($expression as $e) {
            $this->statements[] = $e;
        }

        return $this;
    }

    public function getScopeServiceVar(): string
    {
        return $this->scopeServiceVar;
    }

    public function getScopeVars(): array
    {
        return $this->scopeVars;
    }

    public function addToScopeVars(string ...$name): static
    {
        foreach ($name as $n) {
            if (!in_array($n, $this->scopeVars, true)) {
                $this->validateVar($n, 'Invalid scope variable.');
                $this->scopeVars[] = $n;
            }
        }

        return $this;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function setIsSingleton(?bool $isSingleton): static
    {
        $this->isSingleton = $isSingleton;

        return $this;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function setReturnType(string $returnType): static
    {
        $this->returnType = $returnType;

        return $this;
    }

    private function validateVar(string $var, string $messageWhere): void
    {
        if (1 !== preg_match('/^\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $var)) {
            throw new DefinitionCompileException(
                ltrim(
                    sprintf('%s Variable name "%s" is invalid. Variables in PHP are represented by a dollar sign followed by the name.', $messageWhere, $var)
                )
            );
        }
    }
}

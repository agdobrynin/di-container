<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use UnitEnum;

use function get_debug_type;

final class ValueEntry implements CompilableDefinitionInterface
{
    public function __construct(private readonly mixed $definition) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $expression = Helper::exportSimplestValues($this->definition);
        } catch (InvalidArgumentException $e) {
            throw new DefinitionCompileException($e->getMessage(), previous: $e);
        }

        /** @var non-empty-string $returnType */
        $returnType = $this->definition instanceof UnitEnum
            ? '\\'.get_debug_type($this->definition)
            : get_debug_type($this->definition);

        return new CompiledEntry(expression: $expression, returnType: $returnType);
    }

    public function getDiDefinition(): mixed
    {
        return $this->definition;
    }
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use UnitEnum;

use function get_debug_type;
use function is_string;
use function sprintf;

final class ParameterEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionParameterInterface $parameter,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
    ) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        $parameterName = $this->getParameterName();

        try {
            $value = $this->diContainerDefinitions->getContainer()
                ->parameters()
                ->get($parameterName)
            ;
        } catch (ParameterExceptionInterface|ParameterNotFoundExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('An error occurred when receiving the value of the container parameter "%s".', $parameterName),
                previous: $e,
            );
        }

        try {
            $expression = Helper::exportSimplestValues($value);
        } catch (InvalidArgumentException $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot compile the value of the container parameter "%s".', $parameterName),
                previous: $e,
            );
        }

        $returnType = $value instanceof UnitEnum
            ? '\\'.get_debug_type($value)
            : get_debug_type($value);

        return new CompiledEntry(expression: $expression, returnType: $returnType);
    }

    public function getDiDefinition(): DiDefinitionParameterInterface
    {
        return $this->parameter;
    }

    /**
     * @return non-empty-string
     */
    private function getParameterName(): string
    {
        if ('' !== $this->parameter->getDefinition()) {
            return $this->parameter->getDefinition();
        }

        if (is_string($this->parameter->getContext()) && '' !== $this->parameter->getContext()) {
            return $this->parameter->getContext();
        }

        throw new DefinitionCompileException(
            'Cannot compile container parameter definition. Parameter name must be non-empty string.'
        );
    }
}

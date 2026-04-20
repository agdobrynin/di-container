<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterRuntimeInterface;

use function is_string;
use function rtrim;
use function sprintf;
use function var_export;

final class ParameterRuntimeEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionParameterRuntimeInterface $parameter,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
    ) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        $parameterName = $this->getParameterName();

        if ($this->diContainerDefinitions->getContainer()->parameters()->has($parameterName)) {
            throw new DefinitionCompileException(
                sprintf('The container\'s runtime parameter "%s" must be set in the container at runtime. Use the DiContainerInterface::parameters()->set() method.', $parameterName)
            );
        }

        $compiledEntry = new CompiledEntry(
            scopeServiceVar: '$parameters',
            scopeVars: [...$scopeVars, $containerVar],
        );
        $compiledEntry->addToStatements(
            sprintf('%s = %s->parameters()', $compiledEntry->getScopeServiceVar(), $containerVar)
        );
        $exceptionMessage = rtrim(
            sprintf('The container parameter "%s" must be set in the container at runtime. %s', $parameterName, $this->parameter->getMessage())
        );

        $exceptionExpression = sprintf('throw new \Kaspi\DiContainer\Exception\ParameterNotFoundException(%s)', var_export($exceptionMessage, true));

        $compiledEntry->addToStatements(
            sprintf('if (!%s->has(%s)) %s', $compiledEntry->getScopeServiceVar(), var_export($this->getParameterName(), true), $exceptionExpression)
        );

        $compiledEntry->setExpression(
            sprintf('%s->get(%s)', $compiledEntry->getScopeServiceVar(), var_export($this->getParameterName(), true))
        );

        return $compiledEntry;
    }

    public function getDiDefinition(): DiDefinitionParameterRuntimeInterface
    {
        return $this->parameter;
    }

    /**
     * @return non-empty-string
     *
     * @throws DefinitionCompileException
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
            'Cannot compile container parameter runtime definition. Parameter name must be non-empty string.'
        );
    }
}

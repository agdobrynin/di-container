<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Helper as CommonHelper;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

use function implode;
use function sprintf;

final class ObjectEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionAutowireInterface $definition,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
        private readonly DiDefinitionTransformerInterface $transformer,
    ) {}

    public function compile(string $containerVariableName, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $argBuilder = $this->definition->exposeArgumentBuilder(
                $this->diContainerDefinitions->getContainer()
            );
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot provide constructor arguments to a object definition "%s".', $this->definition->getIdentifier()),
                previous: $e,
            );
        }

        try {
            $setupArgBuilders = $this->definition->exposeSetupArgumentBuilders(
                $this->diContainerDefinitions->getContainer()
            );
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot provide setter method arguments to a object definition "%s".', $this->definition->getIdentifier()),
                previous: $e,
            );
        }

        try {
            $args = null === $argBuilder
                ? []
                : $argBuilder->build();
        } catch (ArgumentBuilderExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot build arguments for constructor in definition "%s".', $this->definition->getDefinition()->getName()),
                previous: $e,
            );
        }

        $fullyName = '\\'.$this->definition->getDefinition()->getName();
        $objectExpression = sprintf('new %s', $fullyName);
        $scopeServiceVariableName = Helper::genUniqueVarName('$object', $containerVariableName, $scopeVariableNames);

        $isSingleton = $this->definition->isSingleton() ?? $this->diContainerDefinitions->isSingletonDefinitionDefault();

        if ([] === $args && [] === $setupArgBuilders) {
            return new CompiledEntry($objectExpression, $isSingleton, '', $scopeServiceVariableName, $scopeVariableNames, $fullyName);
        }

        $compiledArgumentsEntry = Helper::compileArguments(
            $this->transformer,
            $this->diContainerDefinitions,
            $containerVariableName,
            $scopeServiceVariableName,
            $scopeVariableNames,
            $args,
            $context,
        );

        $objectCompiledEntry = new CompiledEntry(
            $objectExpression.$compiledArgumentsEntry->getExpression(),
            $isSingleton,
            $compiledArgumentsEntry->getStatements(),
            $scopeServiceVariableName,
            $compiledArgumentsEntry->getScopeVariables(),
            $fullyName,
        );

        if ([] === $setupArgBuilders) {
            return $objectCompiledEntry;
        }

        $scopeVars = $objectCompiledEntry->getScopeVariables();
        $serviceSetupCompiledStatements = [];

        /**
         * @var ArgumentBuilderInterface $setupArgBuilder
         * @var SetupConfigureMethod     $setupConfigureType
         */
        foreach ($setupArgBuilders as [$setupConfigureType, $setupArgBuilder]) {
            try {
                $setupArgs = $setupArgBuilder->buildByPriorityBindArguments();
            } catch (ArgumentBuilderExceptionInterface $e) {
                throw new DefinitionCompileException(
                    sprintf('Cannot build arguments for setter method in definition "%s".', CommonHelper::functionName($setupArgBuilder->getFunctionOrMethod())),
                    previous: $e
                );
            }

            $compiledSetupArgumentsEntry = Helper::compileArguments(
                $this->transformer,
                $this->diContainerDefinitions,
                $containerVariableName,
                '$argService',
                $scopeVars,
                $setupArgs,
                $context,
            );

            $serviceSetupStatements = $compiledArgumentsEntry->getStatements();

            $methodName = $setupArgBuilder->getFunctionOrMethod()->name;
            $serviceVar = $objectCompiledEntry->getScopeServiceVariableName();

            $serviceSetupStatements .= SetupConfigureMethod::Mutable === $setupConfigureType
                ? sprintf('  %s->%s', $serviceVar, $methodName)
                : sprintf('  %s = %s->%s', $serviceVar, $serviceVar, $methodName);

            $serviceSetupCompiledStatements[] = $serviceSetupStatements.$compiledSetupArgumentsEntry->getExpression().';'.PHP_EOL;
        }

        $statements = '' !== $objectCompiledEntry->getStatements()
            ? sprintf('%s'.PHP_EOL.'%s;', $objectCompiledEntry->getStatements(), $objectCompiledEntry->getExpression())
            : sprintf('%s = %s;'.PHP_EOL, $objectCompiledEntry->getScopeServiceVariableName(), $objectCompiledEntry->getExpression());

        return new CompiledEntry(
            $objectCompiledEntry->getScopeServiceVariableName(),
            $objectCompiledEntry->isSingleton(),
            $statements.implode($serviceSetupCompiledStatements),
            $objectCompiledEntry->getScopeServiceVariableName(),
            $scopeVars,
            $objectCompiledEntry->getReturnType(),
        );
    }

    public function getDiDefinition(): DiDefinitionAutowireInterface
    {
        return $this->definition;
    }
}

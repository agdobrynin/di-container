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
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

use function sprintf;

final class ObjectEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionAutowireInterface $definition,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
        private readonly DiDefinitionTransformerInterface $transformer,
    ) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $argBuilderConstructor = $this->definition->exposeArgumentBuilder(
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

        $fullyQualifiedClassName = '\\'.$this->definition->getDefinition()->getName();
        $objectExpression = sprintf('new %s', $fullyQualifiedClassName);
        $isSingleton = $this->definition->isSingleton() ?? $this->diContainerDefinitions->isSingletonDefinitionDefault();

        $objectCompiledEntry = new CompiledEntry(
            isSingleton: $isSingleton,
            scopeVars: [...$scopeVars, $containerVar],
            returnType: $fullyQualifiedClassName
        );

        if (null === $argBuilderConstructor && [] === $setupArgBuilders) {
            return $objectCompiledEntry->setExpression($objectExpression);
        }

        $argsConstructorExpression = '';

        if (null !== $argBuilderConstructor) {
            try {
                $constructorArgs = $argBuilderConstructor->build();
            } catch (ArgumentBuilderExceptionInterface $e) {
                throw new DefinitionCompileException(
                    sprintf('Cannot build arguments for constructor class "%s".', $this->definition->getDefinition()->getName()),
                    previous: $e,
                );
            }

            if ([] !== $constructorArgs) {
                try {
                    $argsConstructorExpression = Helper::compileArguments(
                        $objectCompiledEntry,
                        $containerVar,
                        $constructorArgs,
                        $this->transformer,
                        $this->diContainerDefinitions,
                        $context,
                    );
                } catch (DefinitionCompileExceptionInterface $e) {
                    throw new DefinitionCompileException(
                        sprintf('Cannot compile arguments for %s.', CommonHelper::functionName($argBuilderConstructor->getFunctionOrMethod())),
                        previous: $e
                    );
                }
            }
        }

        $compiledObjectConstructor = $objectExpression.$argsConstructorExpression;

        if ([] === $setupArgBuilders) {
            return $objectCompiledEntry
                ->setExpression($compiledObjectConstructor)
            ;
        }

        $objectCreateStatement = sprintf('%s = %s', $objectCompiledEntry->getScopeServiceVar(), $compiledObjectConstructor);
        $objectCompiledEntry->addToStatements($objectCreateStatement);

        /**
         * @var ArgumentBuilderInterface $setupArgBuilder
         * @var SetupConfigureMethod     $setupConfigureType
         */
        foreach ($setupArgBuilders as [$setupConfigureType, $setupArgBuilder]) {
            try {
                $setupArgs = $setupArgBuilder->buildByPriorityBindArguments();
            } catch (ArgumentBuilderExceptionInterface $e) {
                throw new DefinitionCompileException(
                    sprintf('Cannot build arguments for setter method in definition %s.', CommonHelper::functionName($setupArgBuilder->getFunctionOrMethod())),
                    previous: $e
                );
            }

            try {
                $argsSetupMethodExpression = Helper::compileArguments(
                    $objectCompiledEntry,
                    $containerVar,
                    $setupArgs,
                    $this->transformer,
                    $this->diContainerDefinitions,
                    $context,
                );
            } catch (DefinitionCompileExceptionInterface $e) {
                throw new DefinitionCompileException(
                    sprintf('Cannot compile arguments for %s.', CommonHelper::functionName($setupArgBuilder->getFunctionOrMethod())),
                    previous: $e
                );
            }

            $methodName = $setupArgBuilder->getFunctionOrMethod()->name;
            $serviceVar = $objectCompiledEntry->getScopeServiceVar();

            $serviceSetupStatement = SetupConfigureMethod::Mutable === $setupConfigureType
                ? sprintf('%s->%s%s', $serviceVar, $methodName, $argsSetupMethodExpression)
                : sprintf('%s = %s->%s%s', $serviceVar, $serviceVar, $methodName, $argsSetupMethodExpression);

            $objectCompiledEntry->addToStatements($serviceSetupStatement);
        }

        return $objectCompiledEntry->setExpression($objectCompiledEntry->getScopeServiceVar());
    }

    public function getDiDefinition(): DiDefinitionAutowireInterface
    {
        return $this->definition;
    }
}

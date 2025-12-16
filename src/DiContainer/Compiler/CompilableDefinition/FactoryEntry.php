<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Helper as CommonHelper;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

use function sprintf;

use const PHP_EOL;

final class FactoryEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionFactoryInterface $definition,
        private readonly DiContainerInterface $container,
        private readonly DiDefinitionTransformerInterface $transformer,
    ) {}

    public function compile(string $containerVariableName, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $bindArgBuilder = $this->definition->exposeFactoryMethodArgumentBuilder($this->container);
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot to expose arguments for factory method in definition "%s".', $this->definition->getDefinition()),
                previous: $e,
            );
        }

        try {
            $bindArgs = $bindArgBuilder->build();
        } catch (ArgumentBuilderExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot build arguments for factory method %s.', CommonHelper::functionName($bindArgBuilder->getFunctionOrMethod())),
                previous: $e,
            );
        }

        $compiledObjectEntry = (new ObjectEntry($this->definition->getFactoryAutowire(), $this->container, $this->transformer))
            ->compile($containerVariableName, $scopeVariableNames, $context)
        ;

        /** @var non-empty-string $scopeObjectVar */
        $scopeObjectVar = $compiledObjectEntry->getScopeServiceVariableName();
        $factoryStatements = $compiledObjectEntry->getStatements();
        $factoryStatements .= sprintf('%s = %s;'.PHP_EOL, $scopeObjectVar, $compiledObjectEntry->getExpression());

        $compiledFactoryMethodArguments = Helper::compileArguments(
            $this->transformer,
            $this->container,
            $containerVariableName,
            $scopeObjectVar,
            $compiledObjectEntry->getScopeVariables(),
            $bindArgs,
            $context,
        );

        $factoryExpression = sprintf(
            '%s->%s%s',
            $scopeObjectVar,
            $this->definition->getFactoryMethod(),
            $compiledFactoryMethodArguments->getExpression()
        );

        $isSingleton = $this->definition->isSingleton() ?? $this->container->getConfig()->isSingletonServiceDefault();

        return new CompiledEntry(
            $factoryExpression,
            $isSingleton,
            $factoryStatements,
            $scopeObjectVar,
            $compiledFactoryMethodArguments->getScopeVariables(),
        );
    }

    public function getDiDefinition(): DiDefinitionFactoryInterface
    {
        return $this->definition;
    }
}

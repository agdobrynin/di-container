<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Helper as CommonHelper;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

use function count;
use function sprintf;

final class FactoryEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionFactoryInterface $definition,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
        private readonly DiDefinitionTransformerInterface $transformer,
    ) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $bindArgBuilder = $this->definition->exposeFactoryMethodArgumentBuilder(
                $this->diContainerDefinitions->getContainer()
            );
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot provide arguments to a factory method in definition "%s".', $this->definition->getDefinition()),
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

        $compiledObjectEntry = (new ObjectEntry($this->definition->getFactoryAutowire(), $this->diContainerDefinitions, $this->transformer))
            ->compile($containerVar, $scopeVars, $context)
        ;

        $argsFactoryMethodExpression = Helper::compileArguments(
            $compiledObjectEntry,
            $containerVar,
            $bindArgs,
            $this->transformer,
            $this->diContainerDefinitions,
            $context,
        );

        $isSingleton = $this->definition->isSingleton() ?? $this->diContainerDefinitions->isSingletonDefinitionDefault();

        if (0 === count($compiledObjectEntry->getStatements())) {
            $factoryStatements = sprintf('%s = %s', $compiledObjectEntry->getScopeServiceVar(), $compiledObjectEntry->getExpression());
            $compiledObjectEntry->addToStatements($factoryStatements);
        }

        $factoryExpression = sprintf('%s->%s%s', $compiledObjectEntry->getScopeServiceVar(), $this->definition->getFactoryMethod(), $argsFactoryMethodExpression);

        return $compiledObjectEntry->setIsSingleton($isSingleton)
            ->setExpression($factoryExpression)
            ->setReturnType('mixed')
        ;
    }

    public function getDiDefinition(): DiDefinitionFactoryInterface
    {
        return $this->definition;
    }
}

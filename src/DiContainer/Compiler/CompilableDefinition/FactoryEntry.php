<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Helper as CommonHelper;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use ReflectionMethod;

use function sprintf;
use function var_export;

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
            [$factoryConstructor, $factoryMethod] = $this->definition->getDefinition();
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException(
                'Cannot get factory definition. Reason: '.$e->getMessage(),
                previous: $e,
            );
        }

        try {
            $bindArgBuilderFactoryMethod = $this->definition->exposeFactoryMethodArgumentBuilder(
                $this->diContainerDefinitions->getContainer()
            );
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot provide arguments to a factory method %s::%s().', $factoryConstructor, $factoryMethod),
                previous: $e,
            );
        }

        /** @var ReflectionMethod $factoryReflectionMethod */
        $factoryReflectionMethod = $bindArgBuilderFactoryMethod->getFunctionOrMethod();

        try {
            $bindArgs = $bindArgBuilderFactoryMethod->build();
        } catch (ArgumentBuilderExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot build arguments for factory method %s.', CommonHelper::functionName($factoryReflectionMethod)),
                previous: $e,
            );
        }

        $isSingleton = $this->definition->isSingleton() ?? $this->diContainerDefinitions->isSingletonDefinitionDefault();
        $compiledFactory = new CompiledEntry(isSingleton: $isSingleton, scopeVars: [...$scopeVars, $containerVar]);

        try {
            $argsFactoryMethodExpression = Helper::compileArguments(
                $compiledFactory,
                $containerVar,
                $bindArgs,
                $this->transformer,
                $this->diContainerDefinitions,
                $context,
            );
        } catch (DefinitionCompileExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot compile arguments for factory method %s.', CommonHelper::functionName($factoryReflectionMethod)),
                previous: $e
            );
        }

        $factoryExpression = $factoryReflectionMethod->isStatic()
            ? sprintf('\%s::%s', $factoryReflectionMethod->getDeclaringClass()->getName(), $factoryMethod)
            : sprintf('%s->get(%s)->%s', $containerVar, var_export($factoryConstructor, true), $factoryMethod);

        $compiledFactory->setExpression($factoryExpression.$argsFactoryMethodExpression);

        return $compiledFactory;
    }

    public function getDiDefinition(): DiDefinitionFactoryInterface
    {
        return $this->definition;
    }
}

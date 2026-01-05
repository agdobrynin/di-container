<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Closure;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Helper as CommonHelper;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCallableInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use LogicException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use RuntimeException;

use function get_debug_type;
use function is_array;
use function is_callable;
use function is_object;
use function sprintf;
use function var_export;

final class CallableEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiDefinitionCallableInterface $definition,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
        private readonly DiDefinitionTransformerInterface $transformer,
    ) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        if (is_array($this->definition->getDefinition())) {
            /**
             * @var class-string|object $class
             * @var non-empty-string    $method
             */
            [$class, $method] = $this->definition->getDefinition();

            if (is_object($class)) {
                throw new DefinitionCompileException(
                    sprintf('Cannot compile callable definition where class "%s" present as object. Definition [$object, "%s"].', get_debug_type($class), $method)
                );
            }
        }

        try {
            $argBuilder = $this->definition->exposeArgumentBuilder($this->diContainerDefinitions->getContainer());
        } catch (DiDefinitionExceptionInterface $e) {
            if ($this->definition->getDefinition() instanceof Closure) {
                $defAsString = CommonHelper::functionName(new ReflectionFunction($this->definition->getDefinition()));
            } else {
                is_callable($this->definition->getDefinition(), true, $defAsString);
            }

            throw new DefinitionCompileException(
                sprintf('Cannot provide arguments to a callable definition %s.', $defAsString),
                previous: $e
            );
        }

        try {
            $args = $argBuilder->build();
        } catch (ArgumentBuilderExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot build arguments for callable definition %s.', CommonHelper::functionName($argBuilder->getFunctionOrMethod())),
                previous: $e
            );
        }

        $callableCompiledEntry = new CompiledEntry(
            scopeServiceVar: '$closure',
            isSingleton: $this->definition->isSingleton() ?? $this->diContainerDefinitions->isSingletonDefinitionDefault(),
            scopeVars: [...$scopeVars, $containerVar],
        );

        try {
            $argsExpression = Helper::compileArguments(
                $callableCompiledEntry,
                $containerVar,
                $args,
                $this->transformer,
                $this->diContainerDefinitions,
                $context,
            );
        } catch (DefinitionCompileExceptionInterface $e) {
            throw new DefinitionCompileException(
                sprintf('Cannot compile arguments for callable definition %s.', CommonHelper::functionName($argBuilder->getFunctionOrMethod())),
                previous: $e
            );
        }

        $expression = $this->getExpression($argBuilder->getFunctionOrMethod()).$argsExpression;

        return $callableCompiledEntry->setExpression($expression);
    }

    public function getDiDefinition(): DiDefinitionCallableInterface
    {
        return $this->definition;
    }

    private function getExpression(ReflectionFunctionAbstract $fn): string
    {
        if ($fn instanceof ReflectionMethod) {
            return sprintf('[\%s::class, %s]', $fn->getDeclaringClass()->name, var_export($fn->getName(), true));
        }

        if (!$fn->isClosure()) {
            return sprintf('\%s', $fn->getName());
        }

        try {
            /** @var Closure $closure */
            $closure = $this->definition->getDefinition();

            return '('.$this->transformer->getClosureParser()->getCode($closure).')';
        } catch (LogicException|RuntimeException $e) {
            throw new DefinitionCompileException('Cannot compile Closure definition.', previous: $e);
        }
    }
}

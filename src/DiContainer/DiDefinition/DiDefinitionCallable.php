<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Closure;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\Exception\DiDefinitionCompileException;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCompileInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use LogicException;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;

use function explode;
use function get_debug_type;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;
use function strpos;
use function var_export;

final class DiDefinitionCallable implements DiDefinitionArgumentsInterface, DiDefinitionSingletonInterface, DiTaggedDefinitionInterface, DiDefinitionTagArgumentInterface, DiDefinitionCompileInterface
{
    use BindArgumentsTrait {
        bindArguments as private bindArgumentsInternal;
    }
    use TagsTrait;

    /**
     * @var callable
     */
    private $definition;

    private ArgumentBuilderInterface $argBuilder;

    public function __construct(callable $definition, private readonly ?bool $isSingleton = null)
    {
        $this->definition = $definition;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function bindArguments(mixed ...$argument): static
    {
        unset($this->argBuilder);
        $this->bindArgumentsInternal(...$argument);

        return $this;
    }

    public function exposeArgumentBuilder(DiContainerInterface $container): ArgumentBuilderInterface
    {
        return new ArgumentBuilder($this->bindArguments, $this->reflectionFunction(), $container);
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        $this->argBuilder ??= $this->exposeArgumentBuilder($container);

        return ($this->definition)(...ArgumentResolver::resolve($this->argBuilder, $container, $this));
    }

    public function compile(string $containerVariableName, DiContainerInterface $container, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface
    {
        if (is_array($this->definition)) {
            /** @var non-empty-string $method */
            [$class, $method] = $this->definition;
        }

        if (isset($class, $method) && is_object($class)) {
            throw new DiDefinitionCompileException(
                sprintf('Cannot compile callable definition with object [%s, "%s"]', get_debug_type($class), $method)
            );
        }

        try {
            $argBuilder = $this->exposeArgumentBuilder($container);
            $args = $argBuilder->build();
        } catch (DiDefinitionExceptionInterface $e) {
            $defAsString = is_array($this->definition)
                ? sprintf('["%s", "%s"]', var_export($this->definition[0], true), var_export($this->definition[1], true))
                : sprintf('"%s"', var_export($this->definition, true));

            throw new DiDefinitionCompileException(
                sprintf('Cannot compile callable definition %s.', $defAsString),
                previous: $e
            );
        }

        $fn = $this->reflectionFunction();

        try {
            $callableExp = match (true) {
                $this->definition instanceof Closure => '('.(new FinderClosureCode())->getCode($this->definition).')',
                $fn instanceof ReflectionMethod => sprintf('[\%s, %s]', $fn->getDeclaringClass()->name, $fn->getName()),
                default => sprintf('\%s', $fn->getName())
            };
        } catch (LogicException|RuntimeException $e) {
            throw new DiDefinitionCompileException('Cannot compile Closure definition.', previous: $e);
        }

        $compiledArgEntity = Helper::compileArguments('$service', $args, $containerVariableName, $container, $scopeVariableNames, $context);

        // TODO add check return type, not only `mixed` type.
        return new CompiledEntry(
            $callableExp.$compiledArgEntity->getExpression(),
            $this->isSingleton() ?? $container->getConfig()->isSingletonServiceDefault(),
            $compiledArgEntity->getStatements(),
            $compiledArgEntity->getScopeServiceVariableName(),
            $compiledArgEntity->getScopeVariables()
        );
    }

    public function getDefinition(): callable
    {
        return $this->definition;
    }

    private function reflectionFunction(): ReflectionFunction|ReflectionMethod
    {
        return match (true) {
            is_array($this->definition) => new ReflectionMethod(...$this->definition), // @phpstan-ignore argument.type
            is_string($this->definition) && (strpos($this->definition, '::') > 0) => new ReflectionMethod(...explode('::', $this->definition, 2)),
            default => new ReflectionFunction($this->definition) // @phpstan-ignore argument.type
        };
    }
}

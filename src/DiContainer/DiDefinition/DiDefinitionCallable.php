<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
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

use function array_key_last;
use function array_push;
use function explode;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;
use function strpos;
use function uniqid;
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
        if (is_array($this->definition) && is_object($this->definition[0])) {
            throw new DiDefinitionCompileException(
                sprintf('Cannot compile callable definition with object [%s, "%s"]', get_debug_type($this->definition[0]), $this->definition[1])
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

        if ($fn->isClosure()) {
            try {
                $callableExp = '('.(new FinderClosureCode())->getCode($this->definition).')(';
            } catch (LogicException|RuntimeException $e) {
                throw new DiDefinitionCompileException(
                    'Cannot compile Closure definition.',
                    previous: $e
                );
            }
        } elseif ($fn instanceof ReflectionFunction) {
            $callableExp = sprintf('\%s(', $fn->getName());
        } else {
            $callableExp = sprintf('[\%s, %s](', $fn->getDeclaringClass()->name, $fn->getName());
        }

        $scopeServiceVariableName = '$service';

        while (in_array($scopeServiceVariableName, $scopeVariableNames, true)) {
            // TODO check max trying generate variable name?
            $scopeServiceVariableName = uniqid('$service_', true);
        }

        $scopeVariableNames[] = $scopeServiceVariableName;
        $callableStm = '';

        foreach ($args as $argIndexOrName => $arg) {
            /** @var DiDefinitionCompileInterface $argToCompile */
            $argToCompile = $arg instanceof DiDefinitionCompileInterface
                ? $arg
                : new DiDefinitionValue($arg);

            $compiledEntity = $argToCompile->compile($containerVariableName, $container, $scopeVariableNames, $this);
            array_push($scopeVariableNames, ...$compiledEntity->getScopeVariables());

            if ('' !== $compiledEntity->getStatements()) {
                $callableStm .= $compiledEntity->getStatements().PHP_EOL;
                $argExpression = $compiledEntity->getScopeServiceVariableName();
            } else {
                $argExpression = $compiledEntity->getExpression();
            }

            $callableExp .= is_string($argIndexOrName)
                ? sprintf(PHP_EOL.'  %s: %s,', $argIndexOrName, $argExpression)
                : sprintf(PHP_EOL.'  %s,', $argExpression);
        }

        $callableExp .= null !== array_key_last($args)
            ? \PHP_EOL.')'
            : ')';

        $serviceExpression = '' !== $callableStm
            ? $scopeServiceVariableName
            : $callableExp;
        $serviceStatements = '' !== $callableStm
            ? sprintf('%s'.PHP_EOL.'  %s = %s;'.PHP_EOL, $callableStm, $scopeServiceVariableName, $callableExp)
            : '';

        $isSingleton = $this->isSingleton() ?? $container->getConfig()->isSingletonServiceDefault();
        $returnType = null === $fn->getReturnType()
            ? 'mixed'
            : (string) $fn->getReturnType();

        return new CompiledEntry($serviceExpression, $isSingleton, $serviceStatements, $scopeServiceVariableName, $scopeVariableNames, $returnType);
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

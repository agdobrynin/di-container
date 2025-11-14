<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Closure;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\Exception\DiDefinitionCallableException;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

use function call_user_func_array;
use function explode;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;
use function strpos;
use function var_export;

/**
 * @phpstan-import-type NotParsedCallable from DiContainerCallInterface
 * @phpstan-import-type ParsedCallable from DiContainerCallInterface
 */
final class DiDefinitionCallable implements DiDefinitionArgumentsInterface, DiDefinitionSingletonInterface, DiTaggedDefinitionInterface
{
    use BindArgumentsTrait {
        bindArguments as private bindArgs;
    }
    use TagsTrait;

    /**
     * @var NotParsedCallable|ParsedCallable
     */
    private readonly mixed $definition;

    /**
     * @var null|ParsedCallable
     */
    private $parsedDefinition;

    private ArgumentBuilder $argBuilder;

    private ReflectionFunction|ReflectionMethodByDefinition $reflectionFn;

    /**
     * @param NotParsedCallable|ParsedCallable $definition
     */
    public function __construct(array|callable|string $definition, private readonly ?bool $isSingleton = null)
    {
        $this->definition = $definition;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArgs(...$argument);
        unset($this->argBuilder);

        return $this;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        $this->reflectionFn ??= $this->reflectionFn();
        $this->argBuilder ??= new ArgumentBuilder($this->getBindArguments(), $this->reflectionFn, $container);

        $resolvedArgs = [];

        foreach ($this->argBuilder->build() as $argNameOrIndex => $arg) {
            $resolvedArgs[$argNameOrIndex] = $arg instanceof DiDefinitionInterface
                ? $arg->resolve($container)
                : $arg;
        }

        if ($this->reflectionFn instanceof ReflectionMethod) {
            if ($this->reflectionFn->isStatic()) {
                /** @var callable $callable */
                $callable = [$this->reflectionFn->class, $this->reflectionFn->name];

                return call_user_func_array($callable, $resolvedArgs);
            }

            $class = $this->reflectionFn->objectOrClassName;

            if (is_string($class)) {
                try {
                    $class = $container->get($class);
                } catch (ContainerExceptionInterface $e) {
                    throw $this->throw($e, sprintf('Cannot get entry by container identifier "%s"', $class));
                }
            }

            if (!is_callable($callable = [$class, $this->reflectionFn->name])) {
                throw $this->throw(
                    prefixMessage: sprintf('Cannot create callable from %s', var_export($callable, true)),
                );
            }

            return call_user_func_array($callable, $resolvedArgs);
        }

        return call_user_func_array($this->getDefinition(), $resolvedArgs); // @phpstan-ignore argument.type
    }

    /**
     * @return ParsedCallable
     */
    public function getDefinition(): array|callable
    {
        return $this->parsedDefinition ??= $this->parseDefinition($this->definition);
    }

    /**
     * @throws DiDefinitionCallableExceptionInterface
     */
    private function reflectionFn(): ReflectionFunction|ReflectionMethodByDefinition
    {
        try {
            if (is_array($this->getDefinition())) {
                return new ReflectionMethodByDefinition(...$this->getDefinition()); // @phpstan-ignore argument.type
            }

            return new ReflectionFunction($this->getDefinition()); // @phpstan-ignore argument.type
        } catch (ReflectionException $e) {
            throw $this->throw($e);
        }
    }

    /**
     * @param NotParsedCallable $definition
     *
     * @return ParsedCallable
     *
     * @throws DiDefinitionCallableException
     */
    private function parseDefinition(array|callable|string $definition): array|callable
    {
        if (is_string($definition) && strpos($definition, '::') > 0) {
            return explode('::', $definition, 2); // @phpstan-ignore return.type
        }

        if (is_callable($definition)) {
            return !($definition instanceof Closure) && is_object($definition)
                ? [$definition, '__invoke']
                : $definition;
        }

        if (is_array($definition)) {
            // @phpstan-ignore booleanAnd.rightAlwaysTrue, isset.offset, isset.offset
            return isset($definition[0], $definition[1]) && is_string($definition[0]) && is_string($definition[1])
                ? [$definition[0], $definition[1]]
                : throw $this->throw(prefixMessage: 'When the definition is an array, two array elements must be provided as none empty string');
        }

        return [$definition, '__invoke'];
    }

    private function throw(?Throwable $previous = null, string $prefixMessage = 'Cannot convert definition to callable type'): DiDefinitionCallableException
    {
        return new DiDefinitionCallableException(
            message: sprintf('%s. Definition value as: %s.', $prefixMessage, var_export($this->definition, true)),
            previous: $previous,
        );
    }
}

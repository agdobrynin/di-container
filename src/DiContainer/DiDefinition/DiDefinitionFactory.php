<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DTO\PriorityBoundArguments;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
use Kaspi\DiContainer\Interfaces\FreezeInterface;
use Kaspi\DiContainer\Interfaces\ResetInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionMethod;

use function explode;
use function get_debug_type;
use function is_callable;
use function is_string;
use function sprintf;
use function strpos;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 */
final class DiDefinitionFactory implements DiDefinitionFactoryInterface, DiDefinitionArgumentsInterface, ResetInterface, FreezeInterface
{
    use BindArgumentsTrait {
        bindArguments as private bindArgumentsInternal;
    }

    private ArgumentBuilderInterface $factoryMethodArgumentBuilder;

    /**
     * @var array{0: class-string|non-empty-string, 1: non-empty-string}
     */
    private array $verifiedDefinition;

    private mixed $context = null;

    /**
     * @param array{0: class-string|non-empty-string, 1: non-empty-string}|class-string|non-empty-string $definition
     */
    public function __construct(private readonly array|string $definition, private readonly ?bool $isSingleton = null) {}

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArgumentsInternal(...$argument);
        unset($this->factoryMethodArgumentBuilder);

        return $this;
    }

    public function exposeFactoryMethodArgumentBuilder(DiContainerInterface $container): ArgumentBuilderInterface
    {
        if (isset($this->factoryMethodArgumentBuilder)) {
            return $this->factoryMethodArgumentBuilder;
        }

        [$factoryConstructor, $factoryMethod] = $this->getDefinition();

        if (is_callable([$factoryConstructor, $factoryMethod])) {
            $reflectionMethod = new ReflectionMethod($factoryConstructor, $factoryMethod);

            return $this->factoryMethodArgumentBuilder = $this->configureArgumentBuilder($reflectionMethod, $container);
        }

        try {
            $factoryAutowire = $container->getDefinition($factoryConstructor);
        } catch (ContainerExceptionInterface $e) {
            throw new DiDefinitionException(
                sprintf('Cannot get factory constructor via container definition "%s".', $factoryConstructor),
                previous: $e,
            );
        }

        if (!$factoryAutowire instanceof DiDefinitionAutowireInterface) {
            throw new DiDefinitionException(
                sprintf('The factory constructor was obtained through the container identifier "%s",  which should be represented as a container definition implementing the %s interface. Got definition type: "%s".', $factoryConstructor, DiDefinitionAutowireInterface::class, get_debug_type($factoryAutowire)),
            );
        }

        try {
            $reflectionMethod = $factoryAutowire->getDefinition()->getMethod($factoryMethod);
        } catch (ReflectionException $e) {
            throw new DiDefinitionException(
                sprintf('Cannot get the factory method %s::%s().', $factoryAutowire->getDefinition()->name, $factoryMethod),
                previous: $e,
            );
        }

        if (!$reflectionMethod->isPublic()) {
            throw new DiDefinitionException(
                sprintf('Factory method %s must be declared with public modifier.', Helper::functionName($reflectionMethod))
            );
        }

        return $this->factoryMethodArgumentBuilder = $this->configureArgumentBuilder($reflectionMethod, $container);
    }

    public function getDefinition(): array
    {
        if (isset($this->verifiedDefinition)) {
            return $this->verifiedDefinition;
        }

        if (is_string($this->definition) && strpos($this->definition, '::') > 0) {
            return $this->verifiedDefinition = explode('::', $this->definition, 2); // @phpstan-ignore assign.propertyType, return.type
        }

        if (is_string($this->definition) && '' !== $this->definition) {
            return $this->verifiedDefinition = [$this->definition, '__invoke'];
        }

        if (isset($this->definition[0], $this->definition[1])
            // @phpstan-ignore notIdentical.alwaysTrue
            && '' !== $this->definition[0]
            // @phpstan-ignore notIdentical.alwaysTrue
            && '' !== $this->definition[1]) {
            return $this->verifiedDefinition = [$this->definition[0], $this->definition[1]];
        }

        throw new DiDefinitionException(
            'The definition for factory should be represented as a class string with the __invoke method, or an array with two elements in the form of a non-empty string.'
        );
    }

    public function getFactoryMethod(): string
    {
        return $this->getDefinition()[1];
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        $argBuilder = $this->exposeFactoryMethodArgumentBuilder($container);

        /** @var ReflectionMethod $method */
        $method = $argBuilder->getFunctionOrMethod();
        $resolvedArguments = $argBuilder->resolve($this);

        if ($method->isStatic()) {
            return $method->invokeArgs(null, $resolvedArguments);
        }

        [$constructor] = $this->getDefinition();

        try {
            /** @var object $object */
            $object = $container->get($constructor);
        } catch (ContainerExceptionInterface $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot resolve factory constructor via container identifier "%s".', $constructor),
                previous: $e
            );
        }

        return $method->invokeArgs($object, $resolvedArguments);
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function reset(): void
    {
        unset(
            $this->factoryMethodArgumentBuilder,
            $this->verifiedDefinition,
        );
    }

    /**
     * Using context as `\Kaspi\Container\DTO\Priority Bound Arguments` to pass priority arguments to a factory method.
     *
     * @param mixed|PriorityBoundArguments $context
     */
    public function setContext(mixed $context): void
    {
        if ($this->isFrozen) {
            throw new DiDefinitionException(
                sprintf('Cannot call \%s::setContext() on a frozen definition.', __CLASS__)
            );
        }

        $this->context = $context;
    }

    public function getContext(): mixed
    {
        return $this->context;
    }

    private function configureArgumentBuilder(ReflectionMethod $reflectionMethod, DiContainerInterface $container): ArgumentBuilder
    {
        if ($this->context instanceof PriorityBoundArguments && [] !== $this->context->arguments) {
            $forcingPriorityUsingBindingArguments = true;
            $args = $this->context->arguments + $this->getBindArguments();
        } else {
            $forcingPriorityUsingBindingArguments = false;
            $args = $this->getBindArguments();
        }

        return new ArgumentBuilder($args, $reflectionMethod, $container, $forcingPriorityUsingBindingArguments);
    }
}

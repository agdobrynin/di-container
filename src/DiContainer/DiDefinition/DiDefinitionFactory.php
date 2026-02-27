<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
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

final class DiDefinitionFactory implements DiDefinitionFactoryInterface, DiDefinitionArgumentsInterface
{
    use BindArgumentsTrait {
        bindArguments as private bindArgumentsInternal;
    }

    private ArgumentBuilderInterface $factoryMethodArgumentBuilder;

    /**
     * @var array{0: class-string|non-empty-string, 1: non-empty-string}
     */
    private array $verifiedDefinition;

    /**
     * @param array{0: class-string|non-empty-string, 1: non-empty-string}|class-string|non-empty-string $definition
     */
    public function __construct(private readonly array|string $definition, private readonly ?bool $isSingleton = null) {}

    public function bindArguments(mixed ...$argument): static
    {
        unset($this->factoryMethodArgumentBuilder);

        $this->bindArgumentsInternal(...$argument);

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

            $this->factoryMethodArgumentBuilder = new ArgumentBuilder($this->getBindArguments(), $reflectionMethod, $container);
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

        return $this->factoryMethodArgumentBuilder = new ArgumentBuilder($this->getBindArguments(), $reflectionMethod, $container);
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
        $resolvedArguments = ArgumentResolver::resolve($argBuilder, $container, $this);

        if ($method->isStatic()) {
            return $method->invokeArgs(null, $resolvedArguments);
        }

        [$constructor] = $this->getDefinition();

        try {
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
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

final class Autowired implements AutowiredInterface
{
    private array $reflectionClasses = [];

    public function resolveInstance(
        ContainerInterface $container,
        \Closure|string $id,
        array $args = []
    ): mixed {
        try {
            if ($id instanceof \Closure) {
                $hash = \spl_object_hash($id);
                $reflectionFunction = $this->reflectionClasses[$hash]
                    ?? $this->reflectionClasses[$hash] = new \ReflectionFunction($id);

                $parameters = $reflectionFunction->getParameters();
                $resolvedArgs = $this->resolveArguments($container, $parameters, $args);

                return $reflectionFunction->invokeArgs($resolvedArgs);
            }

            $reflectionClass = $this->cachedReflectionClass($id);

            if (!$reflectionClass->isInstantiable()) {
                throw new AutowiredException("The [{$id}] class is not instantiable");
            }

            if ($factory = DiFactory::makeFromReflection($reflectionClass)) {
                return $container->get($factory->id)($container);
            }

            $parameters = $reflectionClass->getConstructor()?->getParameters() ?? [];
            $resolvedArgs = $this->resolveArguments($container, $parameters, $args);

            return $reflectionClass->newInstanceArgs($resolvedArgs);
        } catch (\ReflectionException $exception) {
            throw new AutowiredException(
                message: $exception->getMessage(),
                code: $exception->getCode(),
                previous: $exception->getPrevious(),
            );
        }
    }

    public function callMethod(
        ContainerInterface $container,
        string $id,
        string $method,
        array $constructorArgs = [],
        array $methodArgs = []
    ): mixed {
        try {
            $classReflector = $this->cachedReflectionClass($id);
            $methodReflector = $classReflector->getMethod($method);
            $resolvedArgs = $this->resolveArguments($container, $methodReflector->getParameters(), $methodArgs);

            if ($methodReflector->isStatic()) {
                return $methodReflector->invokeArgs(null, $resolvedArgs);
            }

            return $methodReflector->invokeArgs(
                $this->resolveInstance($container, $id, $constructorArgs),
                $resolvedArgs
            );
        } catch (AutowiredExceptionInterface|\ReflectionException $exception) {
            throw new AutowiredException(
                message: $exception->getMessage(),
                code: $exception->getCode(),
                previous: $exception->getPrevious(),
            );
        }
    }

    /**
     * @param \ReflectionParameter[] $parameters
     * @param array<string,mixed>    $inputArgs
     *
     * @throws \ReflectionException
     */
    private function resolveArguments(ContainerInterface $container, array $parameters, array $inputArgs): array
    {
        return match (true) {
            [] === $parameters => $inputArgs,
            [] === $inputArgs => $this->resolveParameters($container, $parameters),
            default => \array_merge($this->resolveParameters(
                $container,
                \array_filter($parameters, static fn (\ReflectionParameter $parameter) => !isset($inputArgs[$parameter->name]))
            ), $inputArgs),
        };
    }

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @throws \ReflectionException
     */
    private function resolveParameters(ContainerInterface $container, array $parameters): array
    {
        return \array_reduce(
            $parameters,
            function (array $dependencies, \ReflectionParameter $parameter) use ($container) {
                $parameterType = $parameter->getType();

                try {
                    if (!$parameterType instanceof \ReflectionNamedType) {
                        throw new AutowiredException(
                            "Unsupported parameter type [{$parameterType}] for [{$parameter->name}]"
                        );
                    }

                    if ($factory = DiFactory::makeFromReflection($parameter)) {
                        $dependencies[$parameter->getName()] = $container->get($factory->id)($container);

                        return $dependencies;
                    }

                    $inject = Inject::makeFromReflection($parameter);

                    $value = match (true) {
                        $parameterType->isBuiltin() => $container->get($inject?->id ?: $parameter->getName()),

                        !$parameterType->isBuiltin() && $inject => $this->resolveParameterByInjectAttribute($container, $inject),

                        ContainerInterface::class === $parameterType->getName() => $container,

                        default => $container->get($parameterType->getName()),
                    };

                    $dependencies[$parameter->getName()] = $value;
                } catch (AutowiredExceptionInterface|ContainerExceptionInterface $exception) {
                    if (!$parameter->isDefaultValueAvailable()) {
                        $where = $parameter->getDeclaringClass()->name.'::'.$parameter->getDeclaringFunction()->name;

                        throw new AutowiredException(
                            message: "Unresolvable dependency [{$parameter}] in [{$where}].",
                            code: $exception->getCode(),
                            previous: $exception,
                        );
                    }

                    $dependencies[$parameter->getName()] = $parameter->getDefaultValue();
                }

                return $dependencies;
            },
            []
        );
    }

    private function resolveParameterByInjectAttribute(ContainerInterface $container, Inject $inject): mixed
    {
        if (\interface_exists($inject->id)) {
            $reflectionClass = $this->cachedReflectionClass($inject->id);

            if ($attribute = $reflectionClass->getAttributes(Service::class)[0] ?? null) {
                return $this->resolveInstance($container, $attribute->newInstance()->id);
            }
        }

        if (!\class_exists($inject->id)) {
            return $container->get($inject->id);
        }

        foreach ($inject->arguments as $argName => $argValue) {
            if (\is_string($argValue) && $container->has($argValue)) {
                $inject->arguments[$argName] = $container->get($argValue);
            } else {
                $inject->arguments[$argName] = $argValue;
            }
        }

        return $this->resolveInstance($container, $inject->id, $inject->arguments);
    }

    private function cachedReflectionClass(string $class): \ReflectionClass
    {
        return $this->reflectionClasses[$class]
            ?? $this->reflectionClasses[$class] = new \ReflectionClass($class);
    }
}

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
    public function __construct(private bool $useAttribute = true) {}

    public function resolveInstance(
        ContainerInterface $container,
        \Closure|string $id,
        array $args = []
    ): mixed {
        try {
            if ($id instanceof \Closure) {
                $reflectionFunction = new \ReflectionFunction($id);
                $resolvedArgs = $this->resolveArguments($container, $reflectionFunction->getParameters(), $args);

                return $reflectionFunction->invokeArgs($resolvedArgs);
            }

            $reflectionClass = new \ReflectionClass($id);

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
            $methodReflector = (new \ReflectionClass($id))->getMethod($method);
            $resolvedArgs = $this->resolveArguments($container, $methodReflector->getParameters(), $methodArgs);

            if ($methodReflector->isStatic()) {
                return $methodReflector->invokeArgs(null, $resolvedArgs);
            }

            $instance = $this->resolveInstance($container, $id, $constructorArgs);

            return $methodReflector->invokeArgs($instance, $resolvedArgs);
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
        $id = $inject->id ?: throw new AutowiredException('Wrong Inject attribute. Got: '.\var_export($inject, true));

        if (\interface_exists($id)
            && ($attribute = (new \ReflectionClass($id))->getAttributes(Service::class)[0] ?? null)) {
            return $this->resolveInstance($container, $attribute->newInstance()->id);
        }

        if (!\class_exists($id)) {
            return $container->get($id);
        }

        foreach ($inject->arguments as $argName => $argValue) {
            $inject->arguments[$argName] = \is_string($argValue) && $container->has($argValue)
                ? $container->get($argValue)
                : $argValue;
        }

        return $this->resolveInstance($container, $id, $inject->arguments);
    }
}

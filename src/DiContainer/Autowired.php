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
    private iterable $sharedInstanceByAttribute = [];

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

            ($reflectionClass = new \ReflectionClass($id))->isInstantiable()
                || throw new AutowiredException("The [{$id}] class is not instantiable");

            if ($this->useAttribute && $factory = DiFactory::makeFromReflection($reflectionClass)) {
                return $this->sharedInstanceByAttribute($factory->id, $factory->isShared, function () use ($factory, $id, $container) {
                    ($factoryClass = new \ReflectionClass($factory->id))->isInstantiable()
                    || throw new AutowiredException("The [{$id}] class is not instantiable");

                    $parameters = $factoryClass->getConstructor()?->getParameters() ?? [];
                    $resolvedArgs = $this->resolveArguments($container, $parameters, $factory->arguments);

                    return $factoryClass->newInstanceArgs($resolvedArgs)($container);
                });
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
        $filter = static fn (\ReflectionParameter $parameter) => !isset($inputArgs[$parameter->name]);

        return match (true) {
            [] === $parameters => $inputArgs,
            [] === $inputArgs => $this->resolveParameters($container, $parameters),
            default => $this->resolveParameters($container, \array_filter($parameters, $filter)) + $inputArgs,
        };
    }

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @throws \ReflectionException
     */
    private function resolveParameters(ContainerInterface $container, array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $parameterType = $parameter->getType();

            try {
                if (!$parameterType instanceof \ReflectionNamedType) {
                    throw new AutowiredException(
                        "Unsupported parameter type [{$parameterType}] for [{$parameter->name}]"
                    );
                }

                if ($this->useAttribute) {
                    if ($factory = DiFactory::makeFromReflection($parameter)) {
                        $dependencies[$parameter->getName()] = $this->sharedInstanceByAttribute($factory->id, $factory->isShared, function () use ($factory, $container) {
                            return $this->resolveInstance($container, $factory->id, $factory->arguments)($container);
                        });

                        continue;
                    }

                    if ($inject = Inject::makeFromReflection($parameter)) {
                        $dependencies[$parameter->getName()] = $this->resolveParameterByInject($container, $inject, $parameterType);

                        continue;
                    }
                }

                $dependencies[$parameter->getName()] = match (true) {
                    $parameterType->isBuiltin() => $container->get($parameter->getName()),
                    ContainerInterface::class === $parameterType->getName() => $container,
                    default => $container->get($parameterType->getName()),
                };
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
        }

        return $dependencies;
    }

    private function resolveParameterByInject(ContainerInterface $container, Inject $inject, \ReflectionNamedType $parameterType): mixed
    {
        $isInterface = \interface_exists($inject->id);
        $isClass = \class_exists($inject->id);

        if ((!$isInterface && !$isClass) || $parameterType->isBuiltin()) {
            return $container->get($inject->id);
        }

        if ($isInterface && $service = Service::makeFromReflection(new \ReflectionClass($inject->id))) {
            return $this->sharedInstanceByAttribute($service->id, $inject->isShared, function () use ($service, $container) {
                return $this->resolveInstance($container, $service->id, $service->arguments);
            });
        }

        return $this->sharedInstanceByAttribute($inject->id, $inject->isShared, function () use ($inject, $container) {
            foreach ($inject->arguments as $argName => $argValue) {
                $inject->arguments[$argName] = \is_string($argValue) && $container->has($argValue)
                    ? $container->get($argValue) : $argValue;
            }

            return $this->resolveInstance($container, $inject->id, $inject->arguments);
        });
    }

    private function sharedInstanceByAttribute(string $id, bool $isShared, callable $resolver): mixed
    {
        if ($isShared && isset($this->sharedInstanceByAttribute[$id])) {
            return $this->sharedInstanceByAttribute[$id];
        }

        return $isShared
            ? $this->sharedInstanceByAttribute[$id] = $resolver()
            : $resolver();
    }
}

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
    public function resolveInstance(
        ContainerInterface $container,
        \Closure|string $id,
        array $args = []
    ): mixed {
        try {
            if ($id instanceof \Closure) {
                $instance = new \ReflectionFunction($id);
                $instanceParameters = $instance->getParameters();
                $resolvedArgs = \array_merge($this->resolveParameters(
                    $container,
                    $this->filterInputArgs($instanceParameters, $args)
                ), $args);

                return $instance->invokeArgs($resolvedArgs);
            }

            $instance = new \ReflectionClass($id);

            if (!$instance->isInstantiable()) {
                throw new AutowiredException("The [{$id}] class is not instantiable");
            }

            if ($factory = DiFactory::makeFromReflection($instance)) {
                return $container->get($factory->id)($container);
            }

            $instanceParameters = $instance->getConstructor()?->getParameters() ?? [];
            $resolvedArgs = \array_merge($this->resolveParameters(
                $container,
                $this->filterInputArgs($instanceParameters, $args)
            ), $args);

            return $instance->newInstanceArgs($resolvedArgs);
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
            $instance = $this->resolveInstance($container, $id, $constructorArgs);
            $classReflector = new \ReflectionClass($instance);
            $methodReflector = $classReflector->getMethod($method);
            $resolvedArgs = \array_merge($this->resolveParameters(
                $container,
                $this->filterInputArgs($methodReflector->getParameters(), $methodArgs)
            ), $methodArgs);

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
        if (\interface_exists($inject->id)
            && $attribute = (new \ReflectionClass($inject->id))
                ->getAttributes(Service::class)[0] ?? null) {
            return $this->resolveInstance($container, $attribute->newInstance()->id);
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

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @return \ReflectionParameter[]
     */
    private function filterInputArgs(array $parameters, array $args): array
    {
        return \array_filter(
            $parameters,
            static fn (\ReflectionParameter $parameter) => !\array_key_exists($parameter->name, $args)
        );
    }
}

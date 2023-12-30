<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

final class Autowired implements AutowiredInterface
{
    public function resolveInstance(
        ContainerInterface $container,
        callable|string $id,
        array $args = []
    ): mixed {
        try {
            if (\is_callable($id)) {
                $instance = new \ReflectionFunction($id);
                $instanceParameters = $instance->getParameters();
                $resolvedArgs = \array_merge($this->resolveParameters(
                    $container,
                    $this->filterInputArgs($instanceParameters, $args)
                ), $args);

                return $instance->invoke(...$resolvedArgs);
            }

            $instance = new \ReflectionClass($id);

            if (!$instance->isInstantiable()) {
                throw new AutowiredException("The [{$id}] class is not instantiable");
            }

            $instanceParameters = $instance->getConstructor()?->getParameters() ?? [];
            $resolvedArgs = \array_merge($this->resolveParameters(
                $container,
                $this->filterInputArgs($instanceParameters, $args)
            ), $args);

            return $instance->newInstance(...$resolvedArgs);
        } catch (\ReflectionException $exception) {
            throw new AutowiredException(
                message: $exception->getMessage(),
                previous: $exception->getPrevious()
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
            $args = $this->resolveParameters(
                $container,
                $this->filterInputArgs($methodReflector->getParameters(), $methodArgs)
            );

            return $methodReflector->invoke(
                $instance,
                ...\array_merge($args, $methodArgs)
            );
        } catch (AutowiredExceptionInterface|\ReflectionException $exception) {
            throw new AutowiredException(
                message: $exception->getMessage(),
                previous: $exception->getPrevious()
            );
        }
    }

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @throws AutowiredException
     */
    private function resolveParameters(ContainerInterface $container, array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $className = $parameter->getDeclaringClass()?->getName() ?: 'Undefined class';
            $parameterName = $parameter->getName();
            $methodName = $parameter->getDeclaringFunction()->name;

            $parameterType = $parameter->getType();
            $isBuildIn = (!$parameterType instanceof \ReflectionNamedType)
                || $parameterType->isBuiltin();
            $parameterTypeName = $parameterType->getName();

            try {
                $dependencies[$parameterName] = match (true) {
                    $container::class === $parameterTypeName,
                    DiContainerInterface::class === $parameterTypeName => $container,
                    default => $container->get(
                        $isBuildIn
                            ? $parameterName
                            : $parameterTypeName
                    ),
                };
            } catch (ContainerExceptionInterface) {
                if (!$parameter->isDefaultValueAvailable()) {
                    throw new AutowiredException("Unresolvable dependency [{$parameter}] in [{$className}::{$methodName}]");
                }

                $dependencies[$parameterName] = $parameter->getDefaultValue();
            }
        }

        return $dependencies;
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

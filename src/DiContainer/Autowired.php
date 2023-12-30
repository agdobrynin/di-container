<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
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
                if ($container::class === $parameterTypeName
                    || DiContainerInterface::class === $parameterTypeName) {
                    $dependencies[$parameterName] = $container;

                    continue;
                }

                if ($attribute = ($parameter->getAttributes(Inject::class)[0] ?? null)) {
                    $inject = $attribute->newInstance();

                    if (null === $inject->id) {
                        $inject->id = $parameterTypeName;
                    }

                    if (!$isBuildIn) {
                        array_walk($inject->arguments, static function (&$val) use ($container) {
                            $val = $container->get($val);
                        });

                        $instanceId = $inject->id;
                        $args = $inject->arguments;

                        if (interface_exists($inject->id)) {
                            if ($attribute = (new \ReflectionClass($inject->id))->getAttributes(Service::class)[0] ?? null) {
                                $service = $attribute->newInstance();
                                $instanceId = $service->id;
                                $args = [];
                            }
                        }

                        $dependencies[$parameterName] = $this->resolveInstance($container, $instanceId, $args);

                        continue;
                    }

                    $dependencies[$parameterName] = $container->get($inject->id);

                    continue;
                }

                $dependencies[$parameterName] = $container->get(
                    $isBuildIn
                            ? $parameterName
                            : $parameterTypeName
                );
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

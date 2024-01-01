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
     * @throws \ReflectionException
     */
    private function resolveParameters(ContainerInterface $container, array $parameters): array
    {
        return array_reduce(
            $parameters,
            function (array $dependencies, \ReflectionParameter $parameter) use ($container) {
                $inject = $parameter->getAttributes(Inject::class)[0] ?? null;
                $isBuildIn = $this->isBuiltinType($parameter);
                $parameterType = $parameter->getType();

                try {
                    $value = match (true) {
                        null !== $inject => $this->resolveByAttribute(
                            $container,
                            $inject->newInstance(),
                            $parameter
                        ),

                        $isBuildIn => $container->get($parameter->getName()),

                        $container::class === $parameterType?->getName(),
                        DiContainerInterface::class === $parameterType?->getName() => $container,

                        default => $container->get($parameterType?->getName()),
                    };

                    $dependencies[$parameter->getName()] = $value;
                } catch (ContainerExceptionInterface) {
                    if (!$parameter->isDefaultValueAvailable()) {
                        $where = $parameter->getDeclaringClass().'::'.$parameter->getDeclaringFunction()->name;

                        throw new AutowiredException("Unresolvable dependency [{$parameter}] in [{$where}]");
                    }

                    $dependencies[$parameter->getName()] = $parameter->getDefaultValue();
                }

                return $dependencies;
            },
            []
        );
    }

    private function isBuiltinType(\ReflectionParameter $parameter): bool
    {
        return (!$parameter->getType() instanceof \ReflectionNamedType)
            || $parameter->getType()->isBuiltin();
    }

    private function resolveByAttribute(
        ContainerInterface $container,
        Inject $inject,
        \ReflectionParameter $parameter
    ): mixed {
        if (null === $inject->id) {
            $inject->id = $parameter->getType()?->getName();
        }

        if ($this->isBuiltinType($parameter)) {
            return $container->get($inject->id);
        }

        foreach ($inject->arguments as $argName => $argValue) {
            $inject->arguments[$argName] = \is_string($argValue) && $container->has($argValue)
                ? $container->get($argValue)
                : $argValue;
        }

        if (interface_exists($inject->id)
            && $attribute = (new \ReflectionClass($inject->id))
                ->getAttributes(Service::class)[0] ?? null) {
            $service = $attribute->newInstance();
            $inject->id = $service->id;
            $inject->arguments = [];
        }

        return $this->resolveInstance(
            $container,
            $inject->id,
            $inject->arguments
        );
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

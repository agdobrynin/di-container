<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

final class Autowired implements AutowiredInterface
{
    public function resolveInstance(
        DiContainerInterface $container,
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

            if ($factory = DiFactory::makeFromReflection($reflectionClass)) {
                try {
                    $container->set($id, $factory->id, $factory->arguments, $factory->isShared);
                } catch (ContainerAlreadyRegisteredException) {
                }

                return $container->get($id)($container);
            }

            $parameters = $reflectionClass->getConstructor()?->getParameters() ?? [];
            $resolvedArgs = $this->resolveArguments($container, $parameters, $args);

            return $reflectionClass->newInstanceArgs($resolvedArgs);
        } catch (\ReflectionException $e) {
            throw new AutowiredException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    public function callMethod(
        DiContainerInterface $container,
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
        } catch (AutowiredExceptionInterface|\ReflectionException $e) {
            throw new AutowiredException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param \ReflectionParameter[] $parameters
     * @param array<string,mixed>    $inputArgs
     *
     * @throws \ReflectionException
     */
    private function resolveArguments(DiContainerInterface $container, array $parameters, array $inputArgs): array
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
    private function resolveParameters(DiContainerInterface $container, array $parameters): array
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

                if ($factory = DiFactory::makeFromReflection($parameter)) {
                    try {
                        $container->set($factory->id, $factory->id, $factory->arguments, $factory->isShared);
                    } catch (ContainerAlreadyRegisteredException) {
                    }

                    $dependencies[$parameter->getName()] = $container->get($factory->id);

                    continue;
                }

                if ($inject = Inject::makeFromReflection($parameter)) {
                    $isInterface = \interface_exists($inject->id);
                    $isClass = \class_exists($inject->id);

                    if ((!$isInterface && !$isClass) || $parameterType->isBuiltin()) {
                        $dependencies[$parameter->getName()] = $container->get($inject->id);

                        continue;
                    }

                    if ($isInterface && $service = Service::makeFromReflection(new \ReflectionClass($inject->id))) {
                        try {
                            $container->set($inject->id, $service->id, $service->arguments, $service->isShared);
                        } catch (ContainerAlreadyRegisteredException) {
                        }

                        $dependencies[$parameter->getName()] = $container->get($inject->id);

                        continue;
                    }

                    foreach ($inject->arguments as $argName => $argValue) {
                        $inject->arguments[$argName] = \is_string($argValue) && $container->has($argValue)
                            ? $container->get($argValue)
                            : $argValue;
                    }

                    $dependencies[$parameter->getName()] = $this
                        ->resolveInstance($container, $inject->id, $inject->arguments)
                    ;
                }

                $dependencies[$parameter->getName()] = match (true) {
                    $parameterType->isBuiltin() => $container->get($parameter->getName()),
                    ContainerInterface::class === $parameterType->getName() => $container,
                    default => $container->get($parameterType->getName()),
                };
            } catch (AutowiredExceptionInterface|ContainerExceptionInterface $e) {
                if (!$parameter->isDefaultValueAvailable()) {
                    $where = $parameter->getDeclaringClass()->name.'::'.$parameter->getDeclaringFunction()->name;

                    throw new AutowiredException("Unresolvable dependency [{$parameter}] in [{$where}].", $e->getCode(), $e);
                }

                $dependencies[$parameter->getName()] = $parameter->getDefaultValue();
            }
        }

        return $dependencies;
    }
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\AutowiredInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\KeyGeneratorForNamedParameterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

final class Autowired implements AutowiredInterface
{
    public function __construct(
        protected KeyGeneratorForNamedParameterInterface $keyGeneratorForNamedParameter
    ) {}

    public function resolveInstance(
        ContainerInterface $container,
        callable|string $id,
        array $args = []
    ): mixed {
        try {
            if (\is_callable($id)) {
                $functionReflector = new \ReflectionFunction($id);
                $functionArgs = $this->resolveParameters(
                    $container,
                    $this->filterInputArgs($functionReflector->getParameters(), $args)
                );

                return $id(...array_merge($functionArgs, $args));
            }

            $classReflector = new \ReflectionClass($id);

            if (!$classReflector->isInstantiable()) {
                throw new AutowiredException("The [{$id}] class is not instantiable");
            }

            if ($constructReflector = $classReflector->getConstructor()) {
                $constructorArgs = $this->resolveParameters(
                    $container,
                    $this->filterInputArgs($constructReflector->getParameters(), $args)
                );

                return $classReflector->newInstance(...\array_merge($constructorArgs, $args));
            }

            return $classReflector->newInstanceWithoutConstructor();
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

    public function getKeyGeneratorForNamedParameter(): KeyGeneratorForNamedParameterInterface
    {
        return $this->keyGeneratorForNamedParameter;
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
            $parameterId = $this->keyGeneratorForNamedParameter
                ->id($className, $methodName, $parameterName)
            ;

            $parameterType = $parameter->getType();
            $isBuildIn = (!$parameterType instanceof \ReflectionNamedType)
                || $parameterType->isBuiltin();

            try {
                $dependencies[$parameterName] = $container->has($parameterId)
                    ? $container->get($parameterId)
                    : $container->get(
                        $isBuildIn
                        ? $parameterName
                        : $parameterType->getName()
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

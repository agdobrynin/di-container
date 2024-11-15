<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiContainerConfigExceptionInterface;
use Psr\Container\ContainerInterface;

if (!\function_exists('Kaspi\DiContainer\diDefinition')) { // @codeCoverageIgnore
    /**
     * @phan-suppress PhanUnreferencedFunction
     *
     * @throws DiContainerConfigExceptionInterface
     */
    function diDefinition(?string $containerKey = null, mixed $definition = null, ?array $arguments = null, ?bool $isSingleton = null): array
    {
        $prepareDefinition = (
            ($definition ? [0 => $definition] : [])
            + ($arguments ? [DiContainerInterface::ARGUMENTS => $arguments] : [])
            + (null !== $isSingleton ? [DiContainerInterface::SINGLETON => $isSingleton] : [])
        ) ?: throw new ContainerException('Definition function is empty.');

        return $containerKey ? [$containerKey => $prepareDefinition] : $prepareDefinition;
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\getParameterReflectionType')) { // @codeCoverageIgnore
    function getParameterReflectionType(\ReflectionParameter $parameter, ContainerInterface $container): ?\ReflectionNamedType
    {
        $reflectionType = $parameter->getType();

        if ($reflectionType instanceof \ReflectionNamedType && !$reflectionType->isBuiltin()) {
            return $parameter->getType();
        }

        if ($reflectionType instanceof \ReflectionUnionType) {
            foreach ($reflectionType->getTypes() as $type) {
                // Get first available non builtin type e.g.
                // __construct(string|Class1|Class2 $dependency) if Class1 has in container will return 'Class1'
                if (!$type->isBuiltin() && $container->has($type->getName())) {
                    return $type;
                }
            }
        }

        return null;
    }
} // @codeCoverageIgnore

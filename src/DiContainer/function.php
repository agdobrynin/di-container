<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Function;

use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiContainerConfigExceptionInterface;
use Psr\Container\ContainerInterface;

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

function getParameterType(\ReflectionParameter $parameter, ContainerInterface $container): ?\ReflectionNamedType
{
    if (($t = $parameter->getType()) instanceof \ReflectionNamedType && !$t->isBuiltin()) {
        return $parameter->getType();
    }

    if (($t = $parameter->getType()) instanceof \ReflectionUnionType) {
        foreach ($t->getTypes() as $type) {
            // Get first available non builtin type e.g.
            // __construct(string|Class1|Class2 $dependency) will return 'Class1'
            if (!$type->isBuiltin() && $container->has($type->getName())) {
                return $type;
            }
        }
    }

    return null;
}

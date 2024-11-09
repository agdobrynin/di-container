<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Psr\Container\ContainerInterface;

trait ParameterTypeResolverTrait
{
    protected static function getParameterType(\ReflectionParameter $parameter, ContainerInterface $container): ?\ReflectionNamedType
    {
        $reflectionType = $parameter->getType();

        if ($reflectionType instanceof \ReflectionNamedType && !$reflectionType->isBuiltin()) {
            return $parameter->getType();
        }

        if ($reflectionType instanceof \ReflectionUnionType) {
            foreach ($reflectionType->getTypes() as $type) {
                // Get first available non builtin type e.g.
                // __construct(string|Class1|Class2 $dependency) will return 'Class1'
                if (!$type->isBuiltin() && $container->has($type->getName())) {
                    return $type;
                }
            }
        }

        return null;
    }
}

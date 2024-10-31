<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Psr\Container\ContainerInterface;

trait ParameterTypeResolverTrait
{
    protected static function getParameterType(\ReflectionParameter $parameter, ContainerInterface $container): ?\ReflectionNamedType
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
}

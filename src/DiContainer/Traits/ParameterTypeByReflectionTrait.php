<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Psr\Container\ContainerInterface;

trait ParameterTypeByReflectionTrait
{
    use PsrContainerTrait;

    public function getParameterTypeByReflection(\ReflectionParameter $parameter): ?\ReflectionNamedType
    {
        $reflectionType = $parameter->getType();

        if ($reflectionType instanceof \ReflectionNamedType && !$reflectionType->isBuiltin()) {
            return $parameter->getType();
        }

        if ($reflectionType instanceof \ReflectionUnionType) {
            foreach ($reflectionType->getTypes() as $type) {
                // Get first available non builtin type e.g.
                // __construct(string|Class1|Class2 $dependency) if Class1 has in container will return 'Class1'
                if (!$type->isBuiltin() && $this->getContainer()->has($type->getName())) {
                    return $type;
                }
            }
        }

        return null;
    }

    public function isParameterCanBeString(\ReflectionParameter $parameter): bool
    {
        // @todo make more effective this method.

        $type = $parameter->getType();

        if (null === $type) {
            return true;
        }

        if (!$type->isBuiltin()) {
            return false;
        }

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName() === 'string';
        }

        foreach ($type->getTypes() as $type) {
            if ($type->isBuiltin() && $type->getName() === 'string') {
                return true;
            }
        }

        return false;
    }

    abstract public function getContainer(): ContainerInterface;
}

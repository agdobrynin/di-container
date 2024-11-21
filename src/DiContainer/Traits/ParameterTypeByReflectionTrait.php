<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Psr\Container\ContainerInterface;

trait ParameterTypeByReflectionTrait
{
    use PsrContainerTrait;

    public function getParameterTypeByReflection(\ReflectionParameter $reflectionParameter): ?\ReflectionNamedType
    {
        $reflectionType = $reflectionParameter->getType();

        if ($reflectionType instanceof \ReflectionNamedType && !$reflectionType->isBuiltin()) {
            return $reflectionParameter->getType();
        }

        if ($reflectionType instanceof \ReflectionUnionType) {
            foreach ($reflectionType->getTypes() as $type) {
                // Get first available non builtin type e.g.
                // __construct(string|Class1|Class2 $dependency) if container identifier Class1 has will return 'Class1'
                if (!$type->isBuiltin() && $this->getContainer()->has($type->getName())) {
                    return $type;
                }
            }
        }

        return null;
    }

    abstract public function getContainer(): ContainerInterface;
}

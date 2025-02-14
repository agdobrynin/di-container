<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;

trait ParameterTypeByReflectionTrait
{
    use DiContainerTrait;

    abstract public function getContainer(): DiContainerInterface;

    /**
     * @return null|non-empty-string
     */
    private function getParameterTypeByReflection(\ReflectionNamedType|\ReflectionUnionType $type): ?string
    {
        if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
            return null;
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $t) { // @phpstan-ignore-line
                /**
                 * @phpstan-var \ReflectionNamedType $t
                 * @phpstan-var non-empty-string $name
                 */
                $name = $t->getName();

                if (!$t->isBuiltin() && $this->getContainer()->has($name)) {
                    return $name;
                }
            }

            return null;
        }

        return $type->getName(); // @phpstan-ignore-line
    }
}

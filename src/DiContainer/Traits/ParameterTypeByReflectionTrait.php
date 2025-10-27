<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Psr\Container\ContainerInterface;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

use function count;
use function Kaspi\DiContainer\functionNameByParameter;
use function sprintf;

trait ParameterTypeByReflectionTrait
{
    /**
     * @return null|non-empty-string
     *
     * @throws AutowireParameterTypeException
     */
    private function getParameterType(ReflectionParameter $parameter, ContainerInterface $container): ?string
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName(); // @phpstan-ignore-line
        }

        if ($type instanceof ReflectionUnionType) {
            $types = [];
            foreach ($type->getTypes() as $t) { // @phpstan-ignore-line
                /**
                 * @phpstan-var ReflectionNamedType $t
                 * @phpstan-var non-empty-string $name
                 */
                $name = $t->getName();

                if (!$t->isBuiltin() && $container->has($name)) {
                    $types[] = $name;
                }
            }

            return match (count($types)) {
                0 => null,
                1 => $types[0],
                default => throw new AutowireParameterTypeException(
                    sprintf('Cannot automatically resolve dependency in %s. Please specify the parameter type for the %s.', functionNameByParameter($parameter), $parameter)
                )
            };
        }

        if ($type instanceof ReflectionIntersectionType) {
            throw new AutowireParameterTypeException(
                sprintf('Cannot automatically resolve dependency in %s. Please specify the parameter type for the %s.', functionNameByParameter($parameter), $parameter)
            );
        }

        return null;
    }
}

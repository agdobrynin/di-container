<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

use function count;
use function sprintf;

final class Helper
{
    /**
     * @return non-empty-string
     *
     * @throws AutowireParameterTypeException
     */
    public static function getParameterTypeHint(ReflectionParameter $parameter, ContainerInterface $container): string
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName(); // @phpstan-ignore-line
        }

        if ($type instanceof ReflectionUnionType) {
            /** @var non-empty-string[] $types */
            $types = [];
            foreach ($type->getTypes() as $t) {
                /**
                 * @phpstan-var ReflectionNamedType $t
                 * @phpstan-var non-empty-string $name
                 */
                $name = $t->getName();

                if (!$t->isBuiltin() && $container->has($name)) {
                    $types[] = $name;
                }
            }

            return 1 === count($types)
                ? $types[0]
                : throw new AutowireParameterTypeException(sprintf('Cannot automatically resolve dependency in %s. Please specify the %s.', self::functionName($parameter->getDeclaringFunction()), $parameter));
        }

        throw new AutowireParameterTypeException(
            sprintf('Cannot automatically resolve dependency in %s. Please specify the %s.', self::functionName($parameter->getDeclaringFunction()), $parameter)
        );
    }

    public static function functionName(ReflectionFunctionAbstract $fn): string
    {
        $fnName = $fn->isClosure() && false !== $fn->getFileName() && false !== $fn->getStartLine()
            ? sprintf('%s{closure:%s:%d}()', $fn->inNamespace() ? $fn->getNamespaceName().'::' : '', $fn->getFileName(), $fn->getStartLine())
            : $fn->getName().'()';

        return $fn instanceof ReflectionMethod
            ? $fn->getDeclaringClass()->getName().'::'.$fnName
            : $fnName;
    }
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;

trait ParameterTypeByReflectionTrait
{
    use DiContainerTrait;

    abstract public function getContainer(): DiContainerInterface;

    /**
     * @return null|non-empty-string
     *
     * @throws AutowireExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getParameterType(\ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName(); // @phpstan-ignore-line
        }

        if ($type instanceof \ReflectionUnionType) {
            $types = [];
            foreach ($type->getTypes() as $t) { // @phpstan-ignore-line
                /**
                 * @phpstan-var \ReflectionNamedType $t
                 * @phpstan-var non-empty-string $name
                 */
                $name = $t->getName();

                if (!$t->isBuiltin() && $this->getContainer()->has($name)) {
                    $types[] = $name;
                }
            }

            return match (\count($types)) {
                0 => null,
                1 => $types[0],
                default => throw new AutowireException(
                    \sprintf('Cannot automatically resolve dependency. Please specify the parameter type for the argument "$%s". Available types: %s.', $parameter->getName(), '"'.\implode('", "', $types)).'"'
                )
            };
        }

        return null;
    }
}

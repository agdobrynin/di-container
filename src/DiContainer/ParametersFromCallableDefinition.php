<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

class ParametersFromCallableDefinition
{
    /**
     * @return \ReflectionParameter[]
     *
     * @throws \ReflectionException
     */
    public static function make(callable $definition): array
    {
        if ($definition instanceof \Closure
            || (\is_string($definition) && \function_exists($definition))) {
            return (new \ReflectionFunction($definition))->getParameters();
        }

        if (\is_string($definition) && $def = \explode('::', $definition, 2)) {
            return (new \ReflectionMethod($def[0], $def[1]))->getParameters();
        }

        if (\is_array($definition)) {
            return (new \ReflectionMethod($definition[0], $definition[1]))->getParameters();
        }

        return (new \ReflectionMethod($definition, '__invoke'))->getParameters();
    }
}

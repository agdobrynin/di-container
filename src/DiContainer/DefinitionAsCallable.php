<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\DefinitionCallableException;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class DefinitionAsCallable
{
    /**
     * @throws ContainerExceptionInterface|DefinitionCallableExceptionInterface|NotFoundExceptionInterface
     */
    public static function makeFromAbstract(array|string $definition, ContainerInterface $container): callable
    {
        if (\is_callable($definition)) {
            return $definition;
        }

        $def = self::parseDefinition($definition);

        if (\is_string($def[0])) {
            $def[0] = $container->get($def[0]);
        }

        return \is_callable($def)
            ? $def
            : throw new DefinitionCallableException('Definition is not callable. Got: '.\var_export($definition, true));
    }

    /**
     * @return \ReflectionParameter[]
     *
     * @throws \ReflectionException
     */
    public static function reflectParameters(callable $definition): array
    {
        if ($definition instanceof \Closure) {
            return (new \ReflectionFunction($definition))->getParameters();
        }

        if (\is_string($definition) && \function_exists($definition)) {
            return (new \ReflectionFunction($definition))->getParameters();
        }

        if (\is_string($definition) && \strpos($definition, '::') > 0) {
            return (new \ReflectionMethod($definition))->getParameters();
        }

        if (\is_array($definition)) {
            return (new \ReflectionMethod($definition[0], $definition[1]))->getParameters();
        }

        return (new \ReflectionMethod($definition, '__invoke'))->getParameters();
    }

    private static function parseDefinition(array|string $definition): array
    {
        if (\is_array($definition)) {
            isset($definition[0], $definition[1])
                || throw new DefinitionCallableException(
                    'Wrong parameter for parse definition. Got: '.\var_export($definition, true)
                );

            return [$definition[0], $definition[1]];
        }

        return match (true) {
            \strpos($definition, '::') > 0 => \explode('::', $definition, 2),
            default => [$definition, '__invoke'],
        };
    }
}

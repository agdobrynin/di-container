<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Closure;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use ReflectionException;
use ReflectionFunction;

use function explode;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;
use function strpos;
use function var_export;

/**
 * @phpstan-import-type NotParsedCallable from DiContainerCallInterface
 * @phpstan-import-type ParsedCallable from DiContainerCallInterface
 */
final class DefinitionDiCall
{
    /**
     * @param NotParsedCallable $definition
     *
     * @throws DiDefinitionExceptionInterface
     */
    public static function getReflection(array|callable|string $definition): ReflectionFunction|ReflectionMethodByDefinition
    {
        $parsedDefinition = self::parseDefinition($definition);

        try {
            return is_array($parsedDefinition)
                ? new ReflectionMethodByDefinition(...$parsedDefinition) // @phpstan-ignore argument.type
                : new ReflectionFunction($parsedDefinition); // @phpstan-ignore argument.type
        } catch (ReflectionException $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot create callable from %s.', var_export($parsedDefinition, true)),
                previous: $e,
            );
        }
    }

    /**
     * @param NotParsedCallable $definition
     *
     * @return ParsedCallable
     *
     * @throws DiDefinitionException
     */
    private static function parseDefinition(array|callable|string $definition): array|callable
    {
        if (is_string($definition) && strpos($definition, '::') > 0) {
            return explode('::', $definition, 2); // @phpstan-ignore return.type
        }

        if (is_callable($definition)) {
            return !($definition instanceof Closure) && is_object($definition)
                ? [$definition, '__invoke']
                : $definition;
        }

        if (is_array($definition)) {
            // @phpstan-ignore isset.offset, isset.offset
            if (isset($definition[0], $definition[1]) && is_string($definition[0]) && is_string($definition[1])) {
                return [$definition[0], $definition[1]];
            }

            throw new DiDefinitionException(sprintf('When the definition present is an array, two array elements must be provided as none empty string. Got: %s', var_export($definition, true)));
        }

        return [$definition, '__invoke'];
    }
}

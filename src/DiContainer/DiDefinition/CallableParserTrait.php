<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionCallableException;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

trait CallableParserTrait
{
    /**
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function parseCallable(array|callable|string $definition, ContainerInterface $container): callable
    {
        if (\is_callable($definition)) {
            return $definition;
        }

        $parsedDefinition = (static function (array|string $argument): array {
            if (\is_array($argument)) {
                isset($argument[0], $argument[1])
                || throw new DiDefinitionCallableException(
                    'When the definition is an array, two array elements must be provided. Got: '.\var_export($argument, true)
                );

                return [$argument[0], $argument[1]];
            }

            if (\strpos($argument, '::') > 0) {
                return \explode('::', $argument, 2);
            }

            return [$argument, '__invoke'];
        })($definition);

        if (\is_string($parsedDefinition[0])) {
            $parsedDefinition[0] = $container->get($parsedDefinition[0]);
        }

        return \is_callable($parsedDefinition)
            ? $parsedDefinition
            : throw new DiDefinitionCallableException('Definition is not callable. Got: '.\var_export($definition, true));
    }
}
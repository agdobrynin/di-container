<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionSimple;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiContainerConfigExceptionInterface;

if (!\function_exists('Kaspi\DiContainer\diDefinition')) { // @codeCoverageIgnore
    /**
     * @phan-suppress PhanUnreferencedFunction
     *
     * @throws DiContainerConfigExceptionInterface
     */
    function diDefinition(?string $containerKey = null, mixed $definition = null, ?array $arguments = null, ?bool $isSingleton = null): array
    {
        $prepareDefinition = (
            ($definition ? [0 => $definition] : [])
            + ($arguments ? [DiContainerInterface::ARGUMENTS => $arguments] : [])
            + (null !== $isSingleton ? [DiContainerInterface::SINGLETON => $isSingleton] : [])
        ) ?: throw new ContainerException('Definition function is empty.');

        return $containerKey ? [$containerKey => $prepareDefinition] : $prepareDefinition;
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diAutowire')) { // @codeCoverageIgnore
    // @phan-suppress-next-line PhanUnreferencedFunction
    function diAutowire(string $definition, array $arguments = [], ?bool $isSingleton = null): DiDefinitionAutowire
    {
        return new DiDefinitionAutowire($definition, $isSingleton, $arguments);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diCallable')) { // @codeCoverageIgnore
    // @phan-suppress-next-line PhanUnreferencedFunction
    function diCallable(array|callable|string $definition, array $arguments = [], ?bool $isSingleton = null): DiDefinitionCallable
    {
        return new DiDefinitionCallable($definition, $isSingleton, $arguments);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diValue')) { // @codeCoverageIgnore
    // @phan-suppress-next-line PhanUnreferencedFunction
    function diValue(mixed $definition): DiDefinitionSimple
    {
        return new DiDefinitionSimple($definition);
    }
} // @codeCoverageIgnore

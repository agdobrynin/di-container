<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;

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
    function diValue(mixed $definition): DiDefinitionValue
    {
        return new DiDefinitionValue($definition);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diReference')) { // @codeCoverageIgnore
    // @phan-suppress-next-line PhanUnreferencedFunction
    function diReference(string $containerIdentifier): DiDefinitionReference
    {
        return new DiDefinitionReference($containerIdentifier);
    }
} // @codeCoverageIgnore

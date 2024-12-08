<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;

// @todo Remove alias when remove function diReference.
\class_alias(DiDefinitionGet::class, 'Kaspi\DiContainer\DiDefinition\DiDefinitionReference'); // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diAutowire')) { // @codeCoverageIgnore
    // @phan-suppress-next-line PhanUnreferencedFunction
    function diAutowire(string $definition, ?bool $isSingleton = null): DiDefinitionAutowire
    {
        return new DiDefinitionAutowire($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diCallable')) { // @codeCoverageIgnore
    // @phan-suppress-next-line PhanUnreferencedFunction
    function diCallable(array|callable|string $definition, ?bool $isSingleton = null): DiDefinitionCallable
    {
        return new DiDefinitionCallable($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diReference')) { // @codeCoverageIgnore
    /**
     * @deprecated Function diReference() was deprecated, used diGet()
     *
     * @phan-suppress PhanUnreferencedFunction
     * @phan-suppress PhanUndeclaredClassMethod
     * @phan-suppress PhanUndeclaredTypeReturnType
     */
    function diReference(string $containerIdentifier): DiDefinitionReference
    {
        @\trigger_error('Function diReference() was deprecated, used diGet(). This function will remove next major release.', \E_USER_DEPRECATED);

        return new DiDefinitionReference($containerIdentifier);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diGet')) { // @codeCoverageIgnore
    /**
     * @phan-suppress PhanUnreferencedFunction
     */
    function diGet(string $containerIdentifier): DiDefinitionGet
    {
        return new DiDefinitionGet($containerIdentifier);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diValue')) { // @codeCoverageIgnore
    /**
     * @phan-suppress PhanUnreferencedFunction
     */
    function diValue(mixed $value): DiDefinitionValue
    {
        return new DiDefinitionValue($value);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diAsClosure')) { // @codeCoverageIgnore
    /**
     * @phan-suppress PhanUnreferencedFunction
     */
    function diAsClosure(string $definition): DiDefinitionClosure
    {
        return new DiDefinitionClosure($definition);
    }
} // @codeCoverageIgnore

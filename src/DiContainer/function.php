<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionConfigAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;

// @todo Remove alias when remove function diReference.
\class_alias(DiDefinitionGet::class, 'Kaspi\DiContainer\DiDefinition\DiDefinitionReference'); // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diAutowire')) { // @codeCoverageIgnore
    /**
     * @param class-string $definition
     */
    function diAutowire(string $definition, ?bool $isSingleton = null): DiDefinitionConfigAutowireInterface
    {
        return new DiDefinitionAutowire($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diCallable')) { // @codeCoverageIgnore
    /**
     * @param callable|non-empty-array<non-empty-string, non-empty-string>|non-empty-string $definition
     */
    function diCallable(array|callable|string $definition, ?bool $isSingleton = null): DiDefinitionArgumentsInterface
    {
        return new DiDefinitionCallable($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diProxyClosure')) { // @codeCoverageIgnore
    /**
     * @param class-string $definition
     */
    function diProxyClosure(string $definition, ?bool $isSingleton = null): DiDefinitionTagArgumentInterface
    {
        return new DiDefinitionProxyClosure($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diReference')) { // @codeCoverageIgnore
    /**
     * @deprecated Function diReference() was deprecated, used diGet()
     *
     * @param non-empty-string $containerIdentifier
     */
    function diReference(string $containerIdentifier): DiDefinitionReference
    {
        @\trigger_error('Function diReference() was deprecated, used diGet(). This function will remove next major release.', \E_USER_DEPRECATED);

        return new DiDefinitionReference($containerIdentifier);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diGet')) { // @codeCoverageIgnore
    /**
     * @param non-empty-string $containerIdentifier
     */
    function diGet(string $containerIdentifier): DiDefinitionInterface
    {
        return new DiDefinitionGet($containerIdentifier);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diValue')) { // @codeCoverageIgnore
    function diValue(mixed $value): DiDefinitionTagArgumentInterface
    {
        return new DiDefinitionValue($value);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diTaggedAs')) { // @codeCoverageIgnore
    /**
     * @param non-empty-string      $tag
     * @param null|non-empty-string $priorityDefaultMethod
     */
    function diTaggedAs(string $tag, bool $isLazy = true, ?string $priorityDefaultMethod = null, bool $useKeys = true): DiDefinitionNoArgumentsInterface
    {
        return new DiDefinitionTaggedAs($tag, $isLazy, $priorityDefaultMethod, $useKeys);
    }
} // @codeCoverageIgnore

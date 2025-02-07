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
     * @phan-suppress PhanUnreferencedFunction
     *
     * @param class-string $definition
     */
    function diAutowire(string $definition, ?bool $isSingleton = null): DiDefinitionConfigAutowireInterface
    {
        return new DiDefinitionAutowire($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diCallable')) { // @codeCoverageIgnore
    // @phan-suppress-next-line PhanUnreferencedFunction
    function diCallable(array|callable|string $definition, ?bool $isSingleton = null): DiDefinitionArgumentsInterface
    {
        return new DiDefinitionCallable($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diProxyClosure')) { // @codeCoverageIgnore
    /**
     * @phan-suppress PhanUnreferencedFunction
     *
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
    function diGet(string $containerIdentifier): DiDefinitionInterface
    {
        return new DiDefinitionGet($containerIdentifier);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diValue')) { // @codeCoverageIgnore
    /**
     * @phan-suppress PhanUnreferencedFunction
     */
    function diValue(mixed $value): DiDefinitionTagArgumentInterface
    {
        return new DiDefinitionValue($value);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\diTaggedAs')) { // @codeCoverageIgnore
    /**
     * @phan-suppress PhanUnreferencedFunction
     */
    function diTaggedAs(string $tag, bool $isLazy = true, ?string $defaultPriorityMethod = null, bool $requireDefaultPriorityMethod = false): DiDefinitionNoArgumentsInterface
    {
        return new DiDefinitionTaggedAs($tag, $isLazy, $defaultPriorityMethod, $requireDefaultPriorityMethod);
    }
} // @codeCoverageIgnore

if (!\function_exists('Kaspi\DiContainer\tagOptions')) {
    function tagOptions(
        ?string $priorityMethod = null,
        ?string $defaultPriorityMethod = null,
        ?bool $requireDefaultPriorityMethod = null,
    ): array {
        return
            (null !== $priorityMethod ? ['priorityMethod' => $priorityMethod] : [])
            + (null !== $defaultPriorityMethod ? ['defaultPriorityMethod' => $defaultPriorityMethod] : [])
            + (null !== $defaultPriorityMethod && null !== $requireDefaultPriorityMethod ? ['requireDefaultPriorityMethod' => $requireDefaultPriorityMethod] : []);
    }
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;

use function function_exists;

if (!function_exists('Kaspi\DiContainer\diAutowire')) { // @codeCoverageIgnore
    /**
     * @param class-string $definition Fully Qualified Class Name
     */
    function diAutowire(string $definition, ?bool $isSingleton = null): DiDefinitionSetupAutowireInterface&DiDefinitionTagArgumentInterface
    {
        return new DiDefinitionAutowire($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!function_exists('Kaspi\DiContainer\diFactory')) { // @codeCoverageIgnore
    /**
     * @param class-string<DiFactoryInterface> $definition Fully Qualified Class Name
     */
    function diFactory(string $definition, ?bool $isSingleton = null): DiDefinitionSetupAutowireInterface
    {
        return new DiDefinitionFactory($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!function_exists('Kaspi\DiContainer\diCallable')) { // @codeCoverageIgnore
    function diCallable(callable $definition, ?bool $isSingleton = null): DiDefinitionArgumentsInterface&DiDefinitionTagArgumentInterface
    {
        return new DiDefinitionCallable($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!function_exists('Kaspi\DiContainer\diProxyClosure')) { // @codeCoverageIgnore
    /**
     * @param class-string $definition Fully Qualified Class Name
     */
    function diProxyClosure(string $definition, ?bool $isSingleton = null): DiDefinitionTagArgumentInterface
    {
        return new DiDefinitionProxyClosure($definition, $isSingleton);
    }
} // @codeCoverageIgnore

if (!function_exists('Kaspi\DiContainer\diGet')) { // @codeCoverageIgnore
    /**
     * @param non-empty-string $containerIdentifier
     */
    function diGet(string $containerIdentifier): DiDefinitionNoArgumentsInterface
    {
        return new DiDefinitionGet($containerIdentifier);
    }
} // @codeCoverageIgnore

if (!function_exists('Kaspi\DiContainer\diValue')) { // @codeCoverageIgnore
    function diValue(mixed $value): DiDefinitionTagArgumentInterface
    {
        return new DiDefinitionValue($value);
    }
} // @codeCoverageIgnore

if (!function_exists('Kaspi\DiContainer\diTaggedAs')) { // @codeCoverageIgnore
    /**
     * @param non-empty-string       $tag
     * @param null|non-empty-string  $priorityDefaultMethod priority from class::method()
     * @param null|non-empty-string  $key                   identifier of tagged definition from tag options (meta-data)
     * @param null|non-empty-string  $keyDefaultMethod      if $key not found in tag options - try get it from class::method()
     * @param list<non-empty-string> $containerIdExclude    exclude container identifiers from collection
     * @param bool                   $selfExclude           exclude the php calling class from the collection
     */
    function diTaggedAs(string $tag, bool $isLazy = true, ?string $priorityDefaultMethod = null, bool $useKeys = true, ?string $key = null, ?string $keyDefaultMethod = null, array $containerIdExclude = [], bool $selfExclude = true): DiDefinitionNoArgumentsInterface
    {
        return new DiDefinitionTaggedAs($tag, $isLazy, $priorityDefaultMethod, $useKeys, $key, $keyDefaultMethod, $containerIdExclude, $selfExclude);
    }
} // @codeCoverageIgnore

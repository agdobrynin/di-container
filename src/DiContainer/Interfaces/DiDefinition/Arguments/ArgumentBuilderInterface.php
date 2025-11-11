<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition\Arguments;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use ReflectionFunctionAbstract;

/**
 * @phpstan-type DiDefinitionItem DiDefinitionAutowire|DiDefinitionCallable|DiDefinitionGet|DiDefinitionProxyClosure|DiDefinitionTaggedAs|DiDefinitionValue
 */
interface ArgumentBuilderInterface
{
    /**
     * Function or class method witch building arguments.
     */
    public function getFunctionOrMethod(): ReflectionFunctionAbstract;

    public function getContainer(): DiContainerInterface;

    /**
     * Php attributes as highest priority, then binding arguments, then typed parameters.
     *
     * Php attributes for bind argument pass through container configuration.
     *
     * @return array<non-empty-string|non-negative-int, DiDefinitionItem|mixed>
     *
     * @throws AutowireExceptionInterface
     */
    public function build(): array;

    /**
     * Binding arguments as highest priority, then Php attributes, then typed parameters.
     *
     * @return array<non-empty-string|non-negative-int, DiDefinitionItem|mixed>
     *
     * @throws AutowireExceptionInterface
     */
    public function buildByPriorityBindArguments(): array;
}

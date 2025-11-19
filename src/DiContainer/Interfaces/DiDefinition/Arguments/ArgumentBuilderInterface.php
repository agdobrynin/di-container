<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition\Arguments;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use ReflectionFunctionAbstract;

/**
 * @phpstan-import-type BindArgumentsType from DiDefinitionArgumentsInterface
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
     * @return BindArgumentsType
     *
     * @throws ArgumentBuilderExceptionInterface
     */
    public function build(): array;

    /**
     * Binding arguments as highest priority, then Php attributes, then typed parameters.
     *
     * @return BindArgumentsType
     *
     * @throws ArgumentBuilderExceptionInterface
     */
    public function buildByPriorityBindArguments(): array;
}

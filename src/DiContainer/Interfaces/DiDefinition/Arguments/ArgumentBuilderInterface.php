<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition\Arguments;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use ReflectionFunctionAbstract;

/**
 * @phpstan-import-type BindArgumentsType from DiDefinitionArgumentsInterface
 */
interface ArgumentBuilderInterface
{
    /**
     * @return BindArgumentsType
     */
    public function getBindArguments(): array;

    /**
     * Function or class method witch building arguments.
     */
    public function getFunctionOrMethod(): ReflectionFunctionAbstract;

    public function getContainer(): DiContainerInterface;

    /**
     * Returns arguments as definitions that the container can resolve.
     *
     * @return BindArgumentsType
     *
     * @throws ArgumentBuilderExceptionInterface
     */
    public function build(): array;

    /**
     * @return mixed[]
     *
     * @throws ArgumentBuilderExceptionInterface
     */
    public function resolve(?DiDefinitionInterface $context = null): array;
}

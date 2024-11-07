<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

interface ParametersResolverInterface
{
    /**
     * @param \ReflectionParameter[] $reflectionParameters
     * @param array<string, mixed>   $customArguments      Array key - the name of the argument in <reflectionParameter>
     *                                                     that replaces this value
     *
     * @throws ContainerExceptionInterface
     * @throws AutowiredExceptionInterface
     */
    public function resolve(array $reflectionParameters, array $customArguments): array;
}

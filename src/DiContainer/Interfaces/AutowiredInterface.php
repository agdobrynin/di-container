<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Exception\AutowiredException;
use Psr\Container\ContainerInterface;

/**
 * @template TypeResolveClassByAutowired of object
 */
interface AutowiredInterface
{
    /**
     * @param class-string<TypeResolveClassByAutowired>|\Closure $id
     *
     * @return mixed|TypeResolveClassByAutowired
     *
     * @throws AutowiredException
     */
    public function resolveInstance(
        ContainerInterface $container,
        \Closure|string $id,
        array $args = []
    ): mixed;

    /**
     * @param class-string<TypeResolveClassByAutowired> $id
     *
     * @throws AutowiredException
     */
    public function callMethod(
        ContainerInterface $container,
        string $id,
        string $method,
        array $constructorArgs = [],
        array $methodArgs = []
    ): mixed;
}

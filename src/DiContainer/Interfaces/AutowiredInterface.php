<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Exception\AutowiredException;

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
        DiContainerInterface $container,
        \Closure|string $id,
        array $args = []
    ): mixed;

    /**
     * @param class-string<TypeResolveClassByAutowired> $id
     *
     * @throws AutowiredException
     */
    public function callMethod(
        DiContainerInterface $container,
        string|object $id,
        string $method,
        array $constructorArgs = [],
        array $methodArgs = []
    ): mixed;
}

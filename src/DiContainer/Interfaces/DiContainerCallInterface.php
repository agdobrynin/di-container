<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiContainerCallInterface
{
    /**
     * @param array<class-string, null|non-empty-string>|callable|class-string|non-empty-string $definition
     *
     * @throws ContainerExceptionInterface
     * @throws CallCircularDependencyException
     * @throws NotFoundExceptionInterface
     */
    public function call(array|callable|string $definition, array $arguments = []): mixed;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiContainerCallInterface
{
    /**
     * @param <class-string, string|null>[]|class-string|string|callable $definition
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function call(array|callable|string $definition, array $arguments = []): mixed;
}

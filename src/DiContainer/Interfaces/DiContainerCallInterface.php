<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerExceptionInterface;

interface DiContainerCallInterface
{
    /**
     * @param <class-string, string|null>[]|class-string|string|callable $definition
     *
     * @throws ContainerExceptionInterface
     */
    public function call(array|callable|string $definition, array $arguments = []): mixed;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerExceptionInterface;

interface DiContainerSetterInterface
{
    /**
     * @param class-string|string $id
     * @param null|mixed|object   $definition
     *
     * @throws ContainerExceptionInterface
     */
    public function set(string $id, mixed $definition = null, ?array $arguments = null, ?bool $isSingleton = null): static;
}

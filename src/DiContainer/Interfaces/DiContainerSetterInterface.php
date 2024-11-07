<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;

interface DiContainerSetterInterface
{
    /**
     * @param class-string|string $id
     * @param null|mixed|object   $definition
     *
     * @throws ContainerAlreadyRegisteredException
     */
    public function set(string $id, mixed $definition = null, ?array $arguments = null, ?bool $isSingleton = null): static;
}

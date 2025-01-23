<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiContainerInterface extends ContainerInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T>|string $id
     *
     * @return T
     *
     * @throws NotFoundExceptionInterface  no entry was found for **this** identifier
     * @throws ContainerExceptionInterface Error while retrieving the entry.*
     */
    public function get(string $id): mixed;

    /**
     * @param class-string|non-empty-string $id
     * @param mixed|object                  $definition
     *
     * @throws ContainerAlreadyRegisteredExceptionInterface
     * @throws DiDefinitionExceptionInterface
     */
    public function set(string $id, mixed $definition): static;

    /**
     * Get tagged services.
     *
     * @param non-empty-string $tag
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getTaggedAs(string $tag, bool $lazy): iterable;
}

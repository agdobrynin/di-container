<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerInterface;

/**
 * @template T of object
 */
interface DiContainerInterface extends ContainerInterface
{
    /**
     * @param null|mixed|object $abstract
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function set(string $id, mixed $abstract = null, ?array $arguments = null): static;

    /**
     * @param class-string<T>|string $id
     *
     * @return T
     */
    public function get(string $id): mixed;
}

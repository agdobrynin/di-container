<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerInterface;

interface DiContainerInterface extends ContainerInterface
{
    /**
     * @param null|mixed|object $abstract
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function set(string $id, mixed $abstract = null, ?array $arguments = null): static;
}

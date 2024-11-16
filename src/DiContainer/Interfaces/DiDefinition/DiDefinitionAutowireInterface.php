<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionAutowireInterface extends DiDefinitionInterface
{
    public function addArgument(string $name, mixed $value): self;

    public function isSingleton(): ?bool;

    /**
     * @throws AutowiredExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function invoke(ContainerInterface $container, ?bool $useAttribute = null): mixed;
}

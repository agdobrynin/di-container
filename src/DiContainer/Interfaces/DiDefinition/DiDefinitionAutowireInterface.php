<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

interface DiDefinitionAutowireInterface extends DiDefinitionInterface
{
    public function getContainerId(): string;

    public function isSingleton(): bool;

    /**
     * @throws AutowiredExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function invoke(DiContainerInterface $container, ?bool $useAttribute): mixed;
}

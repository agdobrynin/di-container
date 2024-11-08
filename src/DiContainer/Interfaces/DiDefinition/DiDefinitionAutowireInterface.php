<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Exception\CallCircularDependency;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionAutowireInterface extends DiDefinitionInterface
{
    public function getContainerId(): string;

    public function isSingleton(): bool;

    /**
     * @throws AutowiredExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws CallCircularDependency
     */
    public function invoke(DiContainerInterface $container, ?bool $useAttribute): mixed;
}

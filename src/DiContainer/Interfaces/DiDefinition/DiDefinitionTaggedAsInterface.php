<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionTaggedAsInterface extends DiDefinitionInterface
{
    public function setContainer(DiContainerInterface $container): static;

    /**
     * @throws ContainerNeedSetExceptionInterface
     */
    public function getContainer(): DiContainerInterface;

    /**
     * @return iterable<non-empty-string|non-negative-int, mixed>
     *
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getServicesTaggedAs(): iterable;

    /**
     * Set calling service.
     */
    public function setCallingByService(?DiDefinitionAutowireInterface $definitionAutowire = null): static;

    /**
     * Calling service.
     */
    public function getCallingByService(): ?DiDefinitionAutowireInterface;
}

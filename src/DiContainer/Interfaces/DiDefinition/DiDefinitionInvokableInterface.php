<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionInvokableInterface extends DiDefinitionInterface
{
    public function isSingleton(): ?bool;

    public function setUseAttribute(?bool $useAttribute): static;

    public function isUseAttribute(): bool;

    public function setContainer(ContainerInterface $container): static;

    /**
     * @throws ContainerNeedSetExceptionInterface
     */
    public function getContainer(): ContainerInterface;

    /**
     * @throws AutowireExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function invoke(): mixed;
}

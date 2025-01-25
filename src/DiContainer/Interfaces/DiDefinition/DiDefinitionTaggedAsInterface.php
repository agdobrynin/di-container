<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionTaggedAsInterface extends DiDefinitionInterface
{
    public function setContainer(ContainerInterface $container): static;

    /**
     * @throws ContainerNeedSetExceptionInterface
     */
    public function getContainer(): ContainerInterface;

    /**
     * @param iterable<non-empty-string, DiDefinitionInterface> $definitions
     *
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getServicesTaggedAs(iterable $definitions): iterable;
}

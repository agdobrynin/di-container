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
     * @param iterable<non-empty-string, DiDefinitionInterface> $definitions
     *
     * @return iterable<int, mixed>
     *
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getServicesTaggedAs(iterable $definitions): iterable;
}

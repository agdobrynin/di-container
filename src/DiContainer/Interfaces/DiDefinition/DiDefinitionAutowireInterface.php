<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionAutowireInterface extends DiDefinitionInterface
{
    /**
     * If argument is variadic then $value must be wrap array.
     *
     * @param non-empty-string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function addArgument(string $name, mixed $value): static;

    public function isSingleton(): ?bool;

    /**
     * @throws AutowiredExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function invoke(?bool $useAttribute = null): mixed;

    public function setContainer(ContainerInterface $container): static;

    public function getContainer(): ContainerInterface;
}

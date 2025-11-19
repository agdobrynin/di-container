<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;

interface DiDefinitionAutowireInterface extends DiTaggedDefinitionInterface
{
    /**
     * @throws AutowireExceptionInterface
     */
    public function getDefinition(): ReflectionClass;

    public function setContainer(DiContainerInterface $container): static;

    /**
     * @return class-string|non-empty-string
     */
    public function getIdentifier(): string;

    /**
     * @throws AutowireExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws DiDefinitionExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): object;
}

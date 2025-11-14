<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionInterface
{
    public function getDefinition(): mixed;

    /**
     * @throws AutowireExceptionInterface|ContainerExceptionInterface|DiDefinitionCallableExceptionInterface|NotFoundExceptionInterface
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): mixed;
}

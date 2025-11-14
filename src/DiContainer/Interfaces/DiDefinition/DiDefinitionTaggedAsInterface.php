<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use ArrayAccess;
use Countable;
use Iterator;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionTaggedAsInterface extends DiDefinitionInterface
{
    /**
     * The parameter $context set calling service aka DiDefinitionAutowireInterface.
     *
     * @return array<non-empty-string, mixed>|(ArrayAccess&ContainerInterface&Countable&Iterator)|list<mixed>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): iterable;
}

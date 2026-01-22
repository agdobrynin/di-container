<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use ArrayAccess;
use Countable;
use Iterator;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
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
     * @throws DiDefinitionExceptionInterface
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): iterable;

    /**
     * Expose tagged container identifiers with keys.
     *
     * @return iterable<non-empty-string|non-negative-int, non-empty-string>
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function exposeContainerIdentifiers(DiContainerInterface $container, mixed $context = null): iterable;

    /**
     * Tag name.
     *
     * @return non-empty-string
     */
    public function getDefinition(): string;

    public function isLazy(): bool;
}

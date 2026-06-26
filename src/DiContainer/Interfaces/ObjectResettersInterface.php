<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Kaspi\DiContainer\Interfaces\Exceptions\ResetterExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface ObjectResettersInterface
{
    /**
     * Setups resetters for container entries.
     *
     * Deletes previous resetters and creates a new one.
     *
     * The iterator key provides the container identifier,
     * which is used to retrieve a PHP object from the container
     * using the `\Psr\Container\ContainerInterface::get()` method.
     *
     * A resetter can be represented as:
     *  - a method name of a PHP class that performs the reset of a container entry;
     *  - a callable expression that accepts a container entry object;
     *
     * @param iterable<non-empty-string, callable(object $object): void|non-empty-string> $resetters
     *
     * @throws ResetterExceptionInterface
     */
    public function setup(iterable $resetters): void;

    /**
     * @return iterable<non-empty-string, callable(object): void|non-empty-string>
     */
    public function resetters(): iterable;

    /**
     * Resets configured container entries.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResetterExceptionInterface
     */
    public function reset(): void;
}

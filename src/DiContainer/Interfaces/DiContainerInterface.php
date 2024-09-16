<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @template T of object
 */
interface DiContainerInterface extends ContainerInterface
{
    /**
     * Key name for defining arguments in a constructor or method in a php-class.
     *
     * Example of using all array keys in container definitions:
     * ```php
     *      $definition = [
     *          // Arguments for constructor
     *          Acme\SomeClass::class => [
     *              DiContainerInterface::ARGUMENTS => [
     *                  'varFirst' => 10,
     *                  'varSecond' => [1, 2, 3],
     *              ],
     *          ],
     *          Acme\OtherClass::class => [
     *              // Arguments for a constructor
     *              DiContainerInterface::ARGUMENTS => [
     *                   'initValue' => 100
     *              ],
     *         ],
     *      ];
     * ```
     */
    public const ARGUMENTS = 'arguments';

    /**
     * @param null|mixed|object $abstract
     *
     * @throws ContainerExceptionInterface
     */
    public function set(string $id, mixed $abstract = null, ?array $arguments = null): static;

    /**
     * @param class-string<T>|string $id
     *
     * @return T
     */
    public function get(string $id): mixed;
}

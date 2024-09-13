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
     *              ]
     *          ],
     *          Acme\OtherClass::class => [
     *           // Arguments for a constructor
     *           DiContainerInterface::ARGUMENTS => [
     *                   'initValue' => 100
     *           ],
     *           // Method for resolve dependency by method in class Acme\OtherClass
     *           DiContainerInterface::METHOD => [
     *              // method for resolving
     *              DiContainerInterface::NAME => 'view',
     *              // arguments for method "view"
     *              DiContainerInterface::ARGUMENTS => [
     *                  'varOne' => 'str value',
     *                  'varLast' => 80,
     *              ],
     *           ],
     *          ],
     *      ];
     * ```.
     */
    public const ARGUMENTS = 'arguments';

    /**
     * Key name for defining class-method for resolve dependency.
     */
    public const METHOD = 'method';

    /**
     * Key name for defining method-name for resolve dependency.
     * This array key is a subkey of "method".
     */
    public const METHOD_NAME = 'name';

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

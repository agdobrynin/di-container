<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

use Psr\Container\ContainerInterface;

interface DiContainerInterface extends ContainerInterface
{
    /**
     * Key name for defining arguments in a constructor or method in a php-class.
     *
     * Example of using all array keys in container definitions:
     *
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
     *              // Make service as shared (public)
     *              DiContainerInterface::SINGLETON => true,
     *         ],
     *      ];
     */
    public const ARGUMENTS = 'arguments';
    public const SINGLETON = 'singleton';

    /**
     * @template T of object
     *
     * @param class-string<T>|string $id
     *
     * @return T
     */
    public function get(string $id): mixed;
}

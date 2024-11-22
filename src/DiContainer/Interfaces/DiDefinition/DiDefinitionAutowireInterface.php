<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionAutowireInterface extends DiDefinitionInterface
{
    /**
     * If argument is variadic then $value must be wrap array.
     *
     * ⚠ This method replaces the previously defined argument with the same name.
     *
     * @param non-empty-string                                          $name
     * @param DiDefinitionAutowireInterface|DiDefinitionInterface|mixed $value
     *
     * @return $this
     */
    public function addArgument(string $name, mixed $value): static;

    /**
     * Arguments provided by the user.
     * Each item in arguments array must provide a variable name in item key and value.
     *
     * ⚠ This method replaces all previously defined arguments.
     *
     * For example:
     *
     *       [
     *           // raw value
     *           "paramNameOne" => "some value",    // include scalar types, array, null type.
     *           "paramNameTwo" => $definition,     // definition implement DiDefinitionInterface
     *                                              // include DiDefinitionAutowireInterface.
     *       ]
     *
     * @param array<non-empty-string, mixed> $arguments
     *
     * @return $this
     */
    public function addArguments(array $arguments): static;

    public function isSingleton(): ?bool;

    /**
     * @throws AutowiredExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function invoke(?bool $useAttribute = null): mixed;

    public function setContainer(ContainerInterface $container): static;

    /**
     * @throws ContainerNeedSetExceptionInterface
     */
    public function getContainer(): ContainerInterface;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

interface DiDefinitionAutowireInterface extends DiDefinitionInterface
{
    /**
     * Add input argument by index or name.
     * If argument is variadic then $value must be wrap array.
     *
     * ⚠ This method replaces the previously defined argument with the same name.
     *
     * @deprecated Use method bindArguments(). This method will remove next major release.
     *
     * @param int|non-empty-string                                      $name
     * @param DiDefinitionAutowireInterface|DiDefinitionInterface|mixed $value
     *
     * @return $this
     */
    public function addArgument(int|string $name, mixed $value): static;

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
     * @deprecated Use method bindArguments(). This method will remove next major release.
     *
     * @param array<non-empty-string, mixed> $arguments
     *
     * @return $this
     */
    public function addArguments(array $arguments): static;

    /**
     * Arguments provided by the user added by name or index.
     * User can set.
     *
     *      bindArguments(var1: 'value 1', var2: 'value 2')
     *      // bind parameters by name $var1 = 'value 1', $var2 = 'value 2'
     *
     * ⚠ This method replaces all previously defined arguments.
     *
     * @return $this
     */
    public function bindArguments(mixed ...$argument): static;

    public function isSingleton(): ?bool;

    public function setUseAttribute(?bool $useAttribute): static;

    public function isUseAttribute(): bool;

    public function setContainer(ContainerInterface $container): static;

    /**
     * @throws ContainerNeedSetExceptionInterface
     */
    public function getContainer(): ContainerInterface;

    /**
     * @throws AutowireExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function invoke(): mixed;
}

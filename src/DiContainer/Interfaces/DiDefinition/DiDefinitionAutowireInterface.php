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
     * Add argument by name or index.
     * If argument is variadic then $value must be wrap array.
     *
     * ⚠ This method replaces the previously defined argument with the same name.
     *
     * @param int|non-empty-string                                      $name
     * @param DiDefinitionAutowireInterface|DiDefinitionInterface|mixed $value
     *
     * @return $this
     */
    public function addArgument(int|string $name, mixed $value): static;

    /**
     * Arguments provided by the user added by name or index.
     * User can set addArgument(var1: 'value 1', var2: 'value 2') equals ['var1' => 'value 1', 'var2' => 'value 2'].
     *
     * ⚠ This method replaces all previously defined arguments.
     *
     * @return $this
     */
    public function addArguments(mixed ...$argument): static;

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

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

interface DiDefinitionAutowireInterface extends DiDefinitionInterface
{
    public function getContainerId(): string;

    /**
     * @return array<int, mixed|\ReflectionParameter>
     */
    public function getArgumentsForResolving(): array;

    public function isSingleton(): bool;

    public function invoke(array $arguments): mixed;
}

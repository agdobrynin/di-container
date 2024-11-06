<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionAutowireInterface extends DiDefinitionInterface
{
    public function getContainerId(): string;

    /**
     * @return array<int, mixed|\ReflectionParameter>
     */
    public function getParametersForResolving(): array;

    public function isSingleton(): bool;

    public function invoke(array $arguments): mixed;
}

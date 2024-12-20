<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionSetupInterface extends DiDefinitionArgumentsInterface
{
    /**
     * Call setter method for class with input arguments.
     * Calling method may use autowire feature.
     *
     * @param non-empty-string $method
     */
    public function setup(string $method, mixed ...$argument): static;
}

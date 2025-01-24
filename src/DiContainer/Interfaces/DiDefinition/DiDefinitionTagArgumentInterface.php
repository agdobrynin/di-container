<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionTagArgumentInterface
{
    /**
     * Bind tag for services with meta-data.
     *
     * @param non-empty-string               $name
     * @param array<non-empty-string, mixed> $options
     */
    public function bindTag(string $name, array $options): static;
}

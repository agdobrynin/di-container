<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionTagArgumentInterface
{
    /**
     * Bind tag for services with meta-data.
     *
     * @param non-empty-string               $name     tag name
     * @param array<non-empty-string, mixed> $options  tag's meta-data
     * @param null|int                       $priority priority for sorting tag collection
     */
    public function bindTag(string $name, array $options, ?int $priority): static;
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionTagArgumentInterface
{
    /**
     * Bind tag for services with meta-data.
     * $priorityMethod return value for sorting by priority.
     * $priorityMethod result has higher priority then $priority value.
     *
     * @param non-empty-string               $name
     * @param array<non-empty-string, mixed> $options
     * @param null|int                       $priority       priority value for sorting collection
     * @param null|string                    $priorityMethod public static method in class for get priority value
     */
    public function bindTag(string $name, array $options, ?int $priority, ?string $priorityMethod): static;
}

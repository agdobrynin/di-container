<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

/**
 * @phpstan-type TagOptions array<non-empty-string, null|array<non-empty-string, null|scalar>|scalar>
 */
interface DiDefinitionTagArgumentInterface
{
    /**
     * Bind tag for services with meta-data.
     *
     * @param non-empty-string $name    tag name
     * @param TagOptions       $options tag's meta-data
     *
     * @return $this
     */
    public function bindTag(string $name, array $options = [], null|int|string $priority = null): static;
}

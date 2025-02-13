<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionTagArgumentInterface
{
    /**
     * Bind tag for services with meta-data.
     *
     * @param non-empty-string                              $name     tag name
     * @param array<non-empty-string, array<scalar>|scalar> $options  tag's meta-data
     * @param null|int|non-empty-string                     $priority priority for sorting tag collection
     *
     * @return $this
     */
    public function bindTag(string $name, array $options = [], null|int|string $priority = null): static;
}

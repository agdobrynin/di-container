<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

/**
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 *
 * @phpstan-type Tags array<non-empty-string, TagOptions>
 */
interface DiTaggedDefinitionInterface
{
    /**
     * Get bound tags with options.
     *
     * @return Tags
     */
    public function getTags(): array;

    /**
     * Get tag options.
     *
     * @param non-empty-string $name
     *
     * @return null|TagOptions
     */
    public function getTag(string $name): ?array;

    /**
     * Has tag.
     *
     * @param non-empty-string $name
     */
    public function hasTag(string $name): bool;

    /**
     * Get priority for tag.
     *
     * @param non-empty-string $name
     * @param TagOptions       $operationOptions temporary options (meta-data) for operation
     */
    public function geTagPriority(string $name, array $operationOptions = []): int|string|null;

    /**
     * Bind tag for services with meta-data.
     *
     * @param non-empty-string $name     tag name
     * @param TagOptions       $options  tag's meta-data
     * @param null|int|string  $priority priority for sorting tag collection
     *
     * @return $this
     */
    public function bindTag(string $name, array $options = [], int|string|null $priority = null): static;
}

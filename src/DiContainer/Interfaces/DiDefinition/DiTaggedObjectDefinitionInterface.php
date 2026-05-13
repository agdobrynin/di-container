<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

/**
 * @phpstan-import-type Tags from DiTaggedDefinitionInterface
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 *
 * Tagged php class.
 */
interface DiTaggedObjectDefinitionInterface extends DiTaggedDefinitionInterface
{
    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function getTags(): array;

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function hasTag(string $name): bool;

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function getTag(string $name): ?array;

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function geTagPriority(string $name, array $operationOptions = []): int|string|null;

    /**
     * @return class-string
     */
    public function getDefinitionIdentifier(): string;

    public function setContainer(DiContainerInterface $container): static;

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function isImplementInterface(string $interface): bool;

    /**
     * Gets bound tags without bound tags via php attributes.
     *
     * @see DiDefinitionTagArgumentInterface::bindTag()
     *
     * @return Tags
     */
    public function getBoundTags(): array;

    /**
     * Gets bound tags via php attributes only.
     *
     * @return array<non-empty-string, TagOptions>
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function getTagsByAttribute(): array;
}

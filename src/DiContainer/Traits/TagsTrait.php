<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;

use function array_key_exists;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 * @phpstan-import-type Tags from DiTaggedDefinitionInterface
 */
trait TagsTrait
{
    use FreezeTrait;

    /**
     * @var array<non-empty-string, TagOptions>
     */
    private array $tags = [];

    public function bindTag(string $name, array $options = [], int|string|null $priority = null): static
    {
        if ($this->isFrozen) {
            throw new DiDefinitionException(
                sprintf('Cannot call \%s::bindTag() on a frozen definition.', static::class)
            );
        }

        $this->tags[$name] = static::transformTagOptions($options, $priority);

        return $this;
    }

    /**
     * @return Tags
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getTag(string $name): ?array
    {
        return $this->getTags()[$name] ?? null;
    }

    public function hasTag(string $name): bool
    {
        return isset($this->getTags()[$name]);
    }

    /**
     * @param non-empty-string $name
     * @param TagOptions       $operationOptions
     */
    public function geTagPriority(string $name, array $operationOptions = []): int|string|null
    {
        $options = $operationOptions + ($this->getTag($name) ?? []);

        return [] !== $options && array_key_exists('priority', $options) && (is_int($priority = $options['priority']) || is_string($priority))
            ? $priority
            : null;
    }

    /**
     * @param TagOptions $options tag's meta-data
     *
     * @return TagOptions
     */
    private static function transformTagOptions(array $options = [], int|string|null $priority = null): array
    {
        return (null === $priority ? [] : ['priority' => $priority]) + $options;
    }
}

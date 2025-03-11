<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;

use function array_key_exists;
use function is_int;
use function is_string;

/**
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 * @phpstan-import-type Tags from DiTaggedDefinitionInterface
 */
trait TagsTrait
{
    /**
     * @var array<non-empty-string, TagOptions>
     */
    private array $tags = [];

    /**
     * @return $this
     */
    public function bindTag(string $name, array $options = [], null|int|string $priority = null): static
    {
        $this->tags[$name] = $options;

        if (null !== $priority) {
            $this->tags[$name]['priority'] = $priority;
        }

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
        return $this->hasTag($name)
            ? $this->tags[$name]
            : null;
    }

    public function hasTag(string $name): bool
    {
        return [] !== $this->tags && isset($this->tags[$name]);
    }

    /**
     * @param non-empty-string $name
     * @param TagOptions       $operationOptions
     */
    public function geTagPriority(string $name, array $operationOptions = []): null|int|string
    {
        $options = $operationOptions + ($this->getTag($name) ?? []);

        return [] !== $options && array_key_exists('priority', $options) && (is_int($priority = $options['priority']) || is_string($priority))
            ? $priority
            : null;
    }
}

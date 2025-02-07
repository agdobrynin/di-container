<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

trait TagsTrait
{
    private array $tags = [];

    /**
     * @phan-suppress PhanTypeMismatchReturn
     * @phan-suppress PhanUnreferencedPublicMethod
     *
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
     * @phan-suppress PhanUnreferencedPublicMethod
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

    public function geTagPriority(string $name, array $operationOptions = []): null|int|string
    {
        $options = ($this->getTag($name) ?? []) + $operationOptions;

        return $options && \array_key_exists('priority', $options) && (\is_int($priority = $options['priority']) || \is_string($priority))
            ? $priority
            : null;
    }
}

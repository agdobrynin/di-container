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
    public function bindTag(string $name, array $options = ['priority' => 0]): static
    {
        $this->tags[$name] = $options;

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

    public function getOptionPriority(string $name): ?int
    {
        return \array_key_exists('priority', $this->getTag($name) ?? [])
            ? (int) $this->getTag($name)['priority'] // @phan-suppress-current-line PhanTypeArraySuspiciousNullable
            : null;
    }
}

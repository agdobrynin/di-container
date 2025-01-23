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
        return $this->tags[$name] ?? null;
    }
}

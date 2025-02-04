<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;

trait TagsTrait
{
    private array $tags = [];

    /**
     * @phan-suppress PhanTypeMismatchReturn
     * @phan-suppress PhanUnreferencedPublicMethod
     *
     * @return $this
     */
    public function bindTag(string $name, array $options = [], ?int $priority = null): static
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

    public function getPriority(string $name): ?int
    {
        $options = $this->getTag($name);

        if (empty($this->priorityTaggedMethod) || DiDefinitionAutowire::class !== static::class) {
            return \array_key_exists('priority', $options)
                ? (int) $options['priority']
                : null;
        }

        /** @var \ReflectionClass $reflectionClass */
        $reflectionClass = static::getDefinition(); // @phan-suppress-current-line
        $callableMethod = $reflectionClass->getName().'::'.$this->priorityTaggedMethod;

        return \is_callable($callableMethod)
            ? (int) $callableMethod()
            : null;
    }
}

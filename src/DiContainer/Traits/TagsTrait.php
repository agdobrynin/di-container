<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Exception\AutowireException;

trait TagsTrait
{
    private array $tags = [];

    /**
     * @phan-suppress PhanTypeMismatchReturn
     * @phan-suppress PhanUnreferencedPublicMethod
     *
     * @return $this
     */
    public function bindTag(string $name, array $options = ['priority' => 0], ?int $priority = null, ?string $defaultPriorityMethod = null): static
    {
        $this->tags[$name] = $options;

        if (null !== $priority) {
            $this->tags[$name]['priority'] = $priority;
        }

        if (null !== $defaultPriorityMethod) {
            $this->tags[$name]['defaultPriorityMethod'] = $defaultPriorityMethod;
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

    public function getOptionPriority(string $name): ?int
    {
        $options = $this->getTag($name);

        if (null === $options) {
            return null;
        }

        if (DiDefinitionAutowire::class === static::class && \array_key_exists('defaultPriorityMethod', $options)) {
            $defaultPriorityMethod = $options['defaultPriorityMethod'];

            if ('' === \trim($defaultPriorityMethod)) {
                throw new AutowireException('The option "defaultPriorityMethod" must be non-empty string');
            }

            $reflectionClass = static::getDefinition(); // @phan-suppress-current-line PhanUndeclaredStaticMethod

            if (!$reflectionClass->hasMethod($defaultPriorityMethod)) {
                throw new AutowireException(
                    \sprintf('The options has "defaultPriorityMethod" but method "%s" does not exist', $defaultPriorityMethod)
                );
            }

            $method = $reflectionClass->getMethod($defaultPriorityMethod);

            if (!$method->isPublic()) {
                throw new AutowireException(
                    \sprintf('The options has "defaultPriorityMethod" but method "%s" must be declared as public', $defaultPriorityMethod)
                );
            }

            if (!$method->isStatic()) {
                throw new AutowireException(
                    \sprintf('The options has "defaultPriorityMethod" but method "%s" must be declared as static', $defaultPriorityMethod)
                );
            }

            return (int) $method->invoke(null);
        }

        if (\array_key_exists('priority', $options)) {
            return (int) $options['priority'];
        }

        return null;
    }
}

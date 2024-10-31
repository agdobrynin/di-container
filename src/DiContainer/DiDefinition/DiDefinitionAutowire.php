<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\DiDefinitionAutowireInterface;

final class DiDefinitionAutowire implements DiDefinitionAutowireInterface
{
    private \ReflectionClass $reflectionClass;

    public function __construct(private string $id, private string $definition, private bool $isSingleton, private array $arguments = [])
    {
        try {
            ($this->reflectionClass = new \ReflectionClass($this->definition))->isInstantiable()
            || throw new AutowiredException(\sprintf('The [%s] class is not instantiable', $definition));
        } catch (\ReflectionException $e) {
            throw new AutowiredException(message: $e->getMessage(), previous: $e);
        }
    }

    public function getContainerId(): string
    {
        return $this->id;
    }

    public function getArgumentsForResolving(): array
    {
        return \array_map(function (\ReflectionParameter $p) {
            return $this->arguments[$p->name] ?? $p;
        }, $this->reflectionClass->getConstructor()?->getParameters() ?? []);
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function invoke(array $arguments = []): mixed
    {
        try {
            return $this->reflectionClass->newInstanceArgs($arguments);
        } catch (\ReflectionException $e) {
            throw new AutowiredException(message: $e->getMessage(), previous: $e);
        }
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }
}
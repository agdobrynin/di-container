<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiDefinitionAutowireInterface;

final class DiDefinitionClosure implements DiDefinitionAutowireInterface
{
    use ArgumentsForResolvingTrait;

    private \ReflectionFunction $reflectionFunction;

    public function __construct(private string $id, private \Closure $definition, private bool $isSingleton, private array $arguments = [])
    {
        $this->reflectionFunction = new \ReflectionFunction($this->definition);
    }

    public function getArgumentsForResolving(): array
    {
        return $this->prepareArgumentsForResolving($this->reflectionFunction->getParameters(), $this->arguments);
    }

    public function getContainerId(): string
    {
        return $this->id;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function invoke(array $arguments): mixed
    {
        return $this->reflectionFunction->invokeArgs($arguments);
    }

    public function getDefinition(): \Closure
    {
        return $this->definition;
    }
}

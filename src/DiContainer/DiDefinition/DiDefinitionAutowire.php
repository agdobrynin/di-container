<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\ParametersResolverInterface;

final class DiDefinitionAutowire implements DiDefinitionAutowireInterface
{
    private \ReflectionClass $reflectionClass;

    /**
     * @var \ReflectionParameter[]
     */
    private array $reflectedParameters;
    private array $arguments;

    public function __construct(private string $id, private string $definition, private bool $isSingleton, array $arguments = [])
    {
        try {
            ($this->reflectionClass = new \ReflectionClass($this->definition))->isInstantiable()
            || throw new AutowiredException(\sprintf('The [%s] class is not instantiable', $definition));

            $this->reflectedParameters = $this->reflectionClass->getConstructor()?->getParameters() ?? [];
            $this->arguments = $arguments;
        } catch (\ReflectionException $e) {
            throw new AutowiredException(message: $e->getMessage(), previous: $e);
        }
    }

    public function getContainerId(): string
    {
        return $this->id;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function invoke(ParametersResolverInterface $parametersResolver): mixed
    {
        try {
            $resolvedArguments = $parametersResolver->resolve($this->reflectedParameters, $this->getArguments());

            return $this->reflectionClass->newInstanceArgs($resolvedArguments);
        } catch (\ReflectionException $e) {
            throw new AutowiredException(message: $e->getMessage(), previous: $e);
        }
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }
}

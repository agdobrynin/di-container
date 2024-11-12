<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;

final class DiDefinitionAutowire implements DiDefinitionAutowireInterface
{
    use ParametersResolverTrait;

    private \ReflectionClass $reflectionClass;

    /**
     * @throws AutowiredExceptionInterface
     */
    public function __construct(private string $definition, private bool $isSingleton, array $arguments = [])
    {
        try {
            $this->reflectionClass = new \ReflectionClass($this->definition);
            $this->reflectionClass->isInstantiable()
                || throw new AutowiredException(\sprintf('The [%s] class is not instantiable', $definition));

            $this->reflectionParameters = $this->reflectionClass->getConstructor()?->getParameters() ?? [];
            $this->arguments = $arguments;
        } catch (\ReflectionException $e) {
            throw new AutowiredException(message: $e->getMessage(), previous: $e);
        }
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function invoke(DiContainerInterface $container, ?bool $useAttribute): mixed
    {
        if ([] === $this->reflectionParameters) {
            return $this->reflectionClass->newInstance();
        }

        $args = $this->resolveParameters($container, $useAttribute);

        return $this->reflectionClass->newInstanceArgs($args);
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }
}

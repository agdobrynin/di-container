<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Psr\Container\ContainerInterface;

final class DiDefinitionAutowire implements DiDefinitionAutowireInterface
{
    use ParametersResolverTrait;

    private \ReflectionClass $reflectionClass;

    /**
     * @throws AutowiredExceptionInterface
     */
    public function __construct(ContainerInterface $container, private string $definition, private bool $isSingleton, array $arguments = [])
    {
        try {
            $this->container = $container;
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

    public function invoke(?bool $useAttribute): mixed
    {
        if ([] === $this->reflectionParameters) {
            return $this->reflectionClass->newInstanceWithoutConstructor();
        }

        $args = $this->resolveParameters($useAttribute);

        return $this->reflectionClass->newInstanceArgs($args);
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }
}

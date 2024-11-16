<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use Psr\Container\ContainerInterface;

final class DiDefinitionAutowire implements DiDefinitionAutowireInterface
{
    use ParametersResolverTrait;
    use PsrContainerTrait;

    private \ReflectionClass $reflectionClass;

    public function __construct(private \ReflectionClass|string $definition, private bool $isSingleton, array $arguments = [])
    {
        if ($this->definition instanceof \ReflectionClass) {
            $this->reflectionClass = $this->definition;
        }

        $this->arguments = $arguments;
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function invoke(ContainerInterface $container, ?bool $useAttribute): mixed
    {
        $this->getDefinition()->isInstantiable()
            || throw new AutowiredException(\sprintf('The [%s] class is not instantiable', $this->reflectionClass->getName()));
        $this->reflectionParameters ??= $this->reflectionClass->getConstructor()?->getParameters() ?? [];

        if ([] === $this->reflectionParameters) {
            return $this->reflectionClass->newInstanceWithoutConstructor();
        }

        $this->setContainer($container);
        $args = $this->resolveParameters($useAttribute);

        return $this->reflectionClass->newInstanceArgs($args);
    }

    public function getDefinition(): \ReflectionClass
    {
        if ($this->definition instanceof \ReflectionClass) {
            return $this->reflectionClass;
        }

        try {
            return $this->reflectionClass = new \ReflectionClass($this->definition);
        } catch (\ReflectionException $e) {
            throw new AutowiredException(message: $e->getMessage());
        }
    }
}

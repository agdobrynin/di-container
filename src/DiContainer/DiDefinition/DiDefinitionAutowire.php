<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use Psr\Container\ContainerInterface;

final class DiDefinitionAutowire implements DiDefinitionAutowireInterface
{
    use ParametersResolverTrait;
    use PsrContainerTrait;

    private \ReflectionClass $reflectionClass;

    /**
     * @throws AutowiredExceptionInterface
     */
    public function __construct(ContainerInterface $container, \ReflectionClass|string $definition, private bool $isSingleton, array $arguments = [])
    {
        try {
            $this->reflectionClass = \is_string($definition) ? new \ReflectionClass($definition) : $definition;
            $this->reflectionClass->isInstantiable()
                || throw new AutowiredException(\sprintf('The [%s] class is not instantiable', $this->reflectionClass->getName()));

            $this->setContainer($container);
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

    public function getDefinition(): \ReflectionClass
    {
        return $this->reflectionClass;
    }
}

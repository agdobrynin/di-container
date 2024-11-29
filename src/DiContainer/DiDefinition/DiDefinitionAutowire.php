<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;

final class DiDefinitionAutowire implements DiDefinitionAutowireInterface, DiDefinitionIdentifierInterface
{
    use ParametersResolverTrait;
    use PsrContainerTrait;

    private \ReflectionClass $reflectionClass;

    public function __construct(private \ReflectionClass|string $definition, private ?bool $isSingleton = null)
    {
        if ($this->definition instanceof \ReflectionClass) {
            $this->reflectionClass = $this->definition;
        }
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function invoke(): mixed
    {
        if (!$this->getDefinition()->isInstantiable()) {
            throw new AutowireException(\sprintf('The [%s] class is not instantiable', $this->reflectionClass->getName()));
        }

        $this->reflectionParameters ??= $this->reflectionClass->getConstructor()?->getParameters() ?? [];

        if ([] === $this->reflectionParameters) {
            return $this->reflectionClass->newInstanceWithoutConstructor();
        }

        $args = $this->resolveParameters();

        return $this->reflectionClass->newInstanceArgs($args);
    }

    /**
     * @throws AutowireExceptionInterface
     */
    public function getDefinition(): \ReflectionClass
    {
        if ($this->definition instanceof \ReflectionClass) {
            return $this->reflectionClass;
        }

        try {
            return $this->reflectionClass = new \ReflectionClass($this->definition);
        } catch (\ReflectionException $e) {
            throw new AutowireException(message: $e->getMessage());
        }
    }

    public function getIdentifier(): string
    {
        return \is_string($this->definition)
            ? $this->definition
            : $this->reflectionClass->getName();
    }
}

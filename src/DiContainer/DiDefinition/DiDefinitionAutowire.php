<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;

final class DiDefinitionAutowire implements DiDefinitionSetupInterface, DiDefinitionInvokableInterface, DiDefinitionIdentifierInterface
{
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    use PsrContainerTrait;

    private \ReflectionClass $reflectionClass;

    /**
     * Methods for setup service via setters.
     *
     * @var array<non-empty-string, array<int|non-empty-string, mixed>>
     */
    private array $setup = [];

    public function __construct(private \ReflectionClass|string $definition, private ?bool $isSingleton = null)
    {
        if ($this->definition instanceof \ReflectionClass) {
            $this->reflectionClass = $this->definition;
        }
    }

    public function setup(string $method, mixed ...$argument): static
    {
        $this->setup[$method][] = $argument;

        return $this;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function invoke(): mixed
    {
        $reflectionClass = $this->getDefinition();

        if (!$reflectionClass->isInstantiable()) {
            throw new AutowireException(\sprintf('The [%s] class is not instantiable', $reflectionClass->getName()));
        }
        // setup property for resolving parameters in constructor
        $this->reflectionParameters ??= $reflectionClass->getConstructor()?->getParameters() ?? [];
        $this->arguments = $this->getBindArguments();

        /**
         * @var object $object
         */
        $object = [] === $this->reflectionParameters
            ? $reflectionClass->newInstanceWithoutConstructor()
            : $reflectionClass->newInstanceArgs($this->resolveParameters());

        if ([] === $this->setup) {
            return $object;
        }

        foreach ($this->setup as $method => $arguments) {
            if (!$reflectionClass->hasMethod($method)) {
                throw new AutowireException(\sprintf('The method "%s" does not exist', $method));
            }
            // setup property for resolving parameters in method
            if ($this->reflectionParameters = $reflectionClass->getMethod($method)->getParameters()) {
                foreach ($arguments as $argument) {
                    $this->arguments = $argument;
                    $reflectionClass->getMethod($method)->invokeArgs($object, $this->resolveParameters());
                }
            }
        }

        return $object;
    }

    /**
     * @throws AutowireExceptionInterface
     */
    public function getDefinition(): \ReflectionClass
    {
        try {
            return $this->reflectionClass ??= new \ReflectionClass($this->definition);
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

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
use Kaspi\DiContainer\Traits\TagsTrait;

final class DiDefinitionAutowire implements DiDefinitionSetupInterface, DiDefinitionInvokableInterface, DiDefinitionIdentifierInterface
{
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    use PsrContainerTrait;
    use TagsTrait;

    private \ReflectionClass $reflectionClass;

    /**
     * @var \ReflectionParameter[]
     */
    private array $reflectionConstructorParams;

    /**
     * @phan-suppress PhanReadOnlyPrivateProperty
     *
     * @var array<non-empty-string, array<int, \ReflectionParameter>>
     */
    private array $reflectionMethodParams;

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
        /**
         * @var object $object
         */
        $object = [] === $this->getConstructorParams()
            ? $this->getDefinition()->newInstanceWithoutConstructor()
            : $this->getDefinition()->newInstanceArgs($this->resolveParameters($this->getBindArguments(), $this->getConstructorParams()));

        if ([] === $this->setup) {
            return $object;
        }

        foreach ($this->setup as $method => $arguments) {
            if (!$this->getDefinition()->hasMethod($method)) {
                throw new AutowireException(\sprintf('The method "%s" does not exist', $method));
            }

            $this->reflectionMethodParams[$method] ??= $this->getDefinition()->getMethod($method)->getParameters();

            foreach ($arguments as $argument) {
                $this->getDefinition()->getMethod($method)
                    ->invokeArgs($object, $this->resolveParameters($argument, $this->reflectionMethodParams[$method]))
                ;
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

    private function getConstructorParams(): array
    {
        if (isset($this->reflectionConstructorParams)) {
            return $this->reflectionConstructorParams;
        }

        $reflectionClass = $this->getDefinition();

        if (!$reflectionClass->isInstantiable()) {
            throw new AutowireException(\sprintf('The [%s] class is not instantiable', $reflectionClass->getName()));
        }

        return $this->reflectionConstructorParams = $reflectionClass->getConstructor()?->getParameters() ?? [];
    }
}

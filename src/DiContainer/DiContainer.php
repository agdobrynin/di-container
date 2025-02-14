<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @phpstan-import-type NotParsedCallable from DiContainerCallInterface
 * @phpstan-import-type ParsedCallable from DiContainerCallInterface
 */
class DiContainer implements DiContainerInterface, DiContainerSetterInterface, DiContainerCallInterface
{
    use AttributeReaderTrait {
        setContainer as private;
    }
    use DefinitionIdentifierTrait;

    /**
     * Default singleton for definitions.
     */
    protected bool $isSingletonDefault;

    /**
     * @var array<class-string|non-empty-string, mixed>
     */
    protected array $definitions = [];

    /**
     * @var array<class-string|non-empty-string, DiDefinitionInterface|DiDefinitionInvokableInterface|DiDefinitionTaggedAsInterface>
     */
    protected array $diResolvedDefinition = [];

    /**
     * @var array<class-string|non-empty-string, mixed>
     */
    protected array $resolved = [];

    /**
     * @var array<class-string|non-empty-string, bool>
     */
    protected array $resolvingDependencies = [];

    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @throws DiDefinitionExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function __construct(
        iterable $definitions = [],
        protected ?DiContainerConfigInterface $config = null
    ) {
        $this->isSingletonDefault = $this->config?->isSingletonServiceDefault() ?? false;

        foreach ($definitions as $identifier => $definition) {
            $this->set($this->getIdentifier($identifier, $definition), $definition);
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|non-empty-string $id
     *
     * @return mixed|T
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @phpstan-ignore method.templateTypeNotInParameter
     */
    public function get(string $id): mixed
    {
        return $this->resolved[$id] ?? $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->definitions)
            || \array_key_exists($id, $this->resolved)
            || (
                $this->config?->isUseZeroConfigurationDefinition()
                && (\class_exists($id) || \interface_exists($id))
            )
            || $this->isContainer($id);
    }

    /**
     * @param class-string|non-empty-string                                                            $id
     * @param DiDefinitionInterface|DiDefinitionInvokableInterface|DiDefinitionTaggedAsInterface|mixed $definition
     */
    public function set(string $id, mixed $definition): static
    {
        $this->getIdentifier($id, null); // check only $id

        if (\array_key_exists($id, $this->definitions)) {
            throw new ContainerAlreadyRegisteredException(
                \sprintf('Definition identifier [%s] already registered in container.', $id)
            );
        }

        $this->definitions[$id] = $definition;

        return $this;
    }

    /**
     * @phpstan-param NotParsedCallable|ParsedCallable $definition
     */
    public function call(array|callable|string $definition, array $arguments = []): mixed
    {
        try {
            return (new DiDefinitionCallable($definition))
                ->bindArguments(...$arguments)
                ->setContainer($this)
                ->invoke()
            ;
        } catch (AutowireExceptionInterface|DiDefinitionCallableExceptionInterface $e) {
            throw new ContainerException(message: $e->getMessage(), previous: $e);
        }
    }

    public function getContainer(): DiContainerInterface
    {
        return $this; // @codeCoverageIgnore
    }

    /**
     * @return iterable<class-string|non-empty-string, DiDefinitionInterface|DiDefinitionInvokableInterface|DiDefinitionLinkInterface|DiDefinitionTaggedAsInterface|DiTaggedDefinitionAutowireInterface>
     */
    public function getDefinitions(): iterable
    {
        foreach ($this->definitions as $id => $definition) {
            if ($definition instanceof DiDefinitionInterface) {
                yield $id => $definition;
            }
        }
    }

    public function getConfig(): ?DiContainerConfigInterface
    {
        return $this->config;
    }

    /**
     * Resolve dependencies.
     *
     * @param class-string|non-empty-string $id
     *
     * @throws ContainerExceptionInterface
     */
    protected function resolve(string $id): mixed
    {
        try {
            if ($this->isContainer($id)) {
                return $this;
            }

            if (!$this->has($id)) {
                throw new NotFoundException(\sprintf('Unresolvable dependency [%s].', $id));
            }

            $this->checkCyclicalDependencyCall($id);
            $this->resolvingDependencies[$id] = true;

            $diDefinition = $this->resolveDefinition($id);

            if ($diDefinition instanceof DiDefinitionInvokableInterface) {
                // Configure definition.
                $object = ($o = $diDefinition->setContainer($this)->invoke()) instanceof DiFactoryInterface
                    ? $o($this)
                    : $o;

                $isSingleton = $diDefinition->isSingleton() ?? $this->isSingletonDefault;

                return $isSingleton
                    ? $this->resolved[$id] = $object
                    : $object;
            }

            if ($diDefinition instanceof DiDefinitionTaggedAsInterface) {
                return $diDefinition->setContainer($this)
                    ->getServicesTaggedAs()
                ;
            }

            return $this->resolved[$id] = $diDefinition->getDefinition();
        } catch (AutowireExceptionInterface|DiDefinitionCallableExceptionInterface $e) {
            throw new ContainerException(message: $e->getMessage(), previous: $e->getPrevious());
        } finally {
            unset($this->resolvingDependencies[$id]);
        }
    }

    /**
     * @param class-string|non-empty-string $id
     *
     * @throws AutowireExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function resolveDefinition(string $id): DiDefinitionInterface|DiDefinitionInvokableInterface|DiDefinitionTaggedAsInterface
    {
        if (isset($this->diResolvedDefinition[$id])) {
            return $this->diResolvedDefinition[$id];
        }

        $hasDefinition = \array_key_exists($id, $this->definitions);

        if (!$hasDefinition) {
            // @phpstan-ignore argument.type
            $reflectionClass = new \ReflectionClass($id); // @todo come up with a test for throw ReflectionException

            if ($reflectionClass->isInterface()) {
                if ($this->config?->isUseAttribute()
                    && $service = $this->getServiceAttribute($reflectionClass)) {
                    $this->checkCyclicalDependencyCall($service->getIdentifier());
                    $this->resolvingDependencies[$service->getIdentifier()] = true;

                    try {
                        return $this->diResolvedDefinition[$service->getIdentifier()] = $this->resolveDefinition($service->getIdentifier());
                    } finally {
                        unset($this->resolvingDependencies[$service->getIdentifier()]);
                    }
                }

                throw new NotFoundException(\sprintf('Definition not found for identifier %s', $id));
            }

            if ($this->config?->isUseAttribute()
                && $factory = $this->getDiFactoryAttribute($reflectionClass)) {
                return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire(
                    $factory->getIdentifier(),
                    $factory->isSingleton()
                );
            }

            return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire($reflectionClass, $this->isSingletonDefault);
        }

        $rawDefinition = $this->definitions[$id];

        if ($rawDefinition instanceof DiDefinitionLinkInterface) {
            $this->checkCyclicalDependencyCall($rawDefinition->getDefinition());
            $this->resolvingDependencies[$rawDefinition->getDefinition()] = true;

            try {
                return $this->resolveDefinition($rawDefinition->getDefinition());
            } finally {
                unset($this->resolvingDependencies[$rawDefinition->getDefinition()]);
            }
        }

        if ($rawDefinition instanceof DiDefinitionInterface) {
            return $this->diResolvedDefinition[$id] = $rawDefinition;
        }

        if ($rawDefinition instanceof \Closure) {
            return $this->diResolvedDefinition[$id] = new DiDefinitionCallable($rawDefinition, $this->isSingletonDefault);
        }

        return $this->diResolvedDefinition[$id] = new DiDefinitionValue($rawDefinition);
    }

    protected function isContainer(string $id): bool
    {
        return \in_array($id, [ContainerInterface::class, DiContainerInterface::class, __CLASS__], true);
    }

    protected function checkCyclicalDependencyCall(string $id): void
    {
        if (\array_key_exists($id, $this->resolvingDependencies)) {
            $callPath = \implode(' -> ', \array_keys($this->resolvingDependencies)).' -> '.$id;

            throw new CallCircularDependencyException(
                \sprintf('Trying call cyclical dependency. Call dependencies: %s', $callPath)
            );
        }
    }
}

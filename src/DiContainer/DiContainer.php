<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\CallableParserTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class DiContainer implements DiContainerInterface, DiContainerCallInterface
{
    use AttributeReaderTrait;
    use CallableParserTrait;

    /**
     * @var array<class-string|non-empty-string, mixed>
     */
    protected array $definitions = [];

    /**
     * @var array<class-string|non-empty-string, DiDefinitionAutowireInterface|DiDefinitionInterface>
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
     * @param iterable<class-string|non-empty-string, class-string|mixed> $definitions
     *
     * @throws DiDefinitionExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function __construct(
        iterable $definitions = [],
        protected ?DiContainerConfigInterface $config = null
    ) {
        foreach ($definitions as $identifier => $definition) {
            $key = match (true) {
                \is_string($identifier) => $identifier,
                \is_string($definition) => $definition,
                $definition instanceof DiDefinitionIdentifierInterface => $definition->getIdentifier(),
                default => throw new DiDefinitionException(
                    \sprintf('Definition identifier must be a non-empty string. Definition [%s].', \get_debug_type($definition))
                )
            };

            $this->set(id: $key, definition: $definition); // @phan-suppress-current-line PhanPartialTypeMismatchArgument
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|non-empty-string $id
     *
     * @return T
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
            );
    }

    public function set(string $id, mixed $definition): static
    {
        if (($id = \trim($id)) === '') {
            throw new DiDefinitionException('Definition identifier must be a non-empty string.');
        }

        if (\array_key_exists($id, $this->definitions)) {
            throw new ContainerAlreadyRegisteredException("Definition identifier [{$id}] already registered in container.");
        }

        if ($definition instanceof DiDefinitionInterface) {
            $this->definitions[$id] = $definition;

            return $this;
        }

        if ($definition instanceof \Closure) {
            $this->definitions[$id] = new DiDefinitionCallable($definition);

            return $this;
        }

        $this->definitions[$id] = $definition;

        return $this;
    }

    public function call(array|callable|string $definition, array $arguments = []): mixed
    {
        try {
            $callable = $this->parseCallable($definition);

            return (new DiDefinitionCallable($callable, arguments: $arguments))
                ->setContainer($this)
                ->invoke($this->config?->isUseAttribute())
            ;
        } catch (AutowiredExceptionInterface|DiDefinitionCallableExceptionInterface $e) {
            throw new ContainerException(message: $e->getMessage(), previous: $e);
        }
    }

    public function getContainer(): ContainerInterface
    {
        return $this;
    }

    /**
     * Resolve dependencies.
     *
     * @throws ContainerExceptionInterface
     */
    protected function resolve(string $id): mixed
    {
        try {
            if (!\array_key_exists($id, $this->resolved) && \in_array($id, [ContainerInterface::class, DiContainerInterface::class, __CLASS__], true)) {
                return $this->resolved[$id] = $this;
            }

            if (!$this->has($id)) {
                throw new NotFoundException("Unresolvable dependency [{$id}].");
            }

            $this->checkCyclicalDependencyCall($id);
            $this->resolvingDependencies[$id] = true;

            $diDefinition = $this->resolveDefinition($id);

            if ($diDefinition instanceof DiDefinitionAutowireInterface) {
                $o = $diDefinition->setContainer($this)->invoke($this->config?->isUseAttribute());
                $object = $o instanceof DiFactoryInterface
                    ? $o($this)
                    : $o;

                $isSingleton = (
                    $diDefinition->isSingleton()
                    ?? $this->config?->isSingletonServiceDefault()
                    ?? false
                );

                return $isSingleton
                    ? $this->resolved[$id] = $object
                    : $object;
            }

            return $this->resolved[$id] = $diDefinition->getDefinition();
        } catch (AutowiredExceptionInterface|DiDefinitionCallableExceptionInterface $e) {
            throw new ContainerException(message: $e->getMessage(), previous: $e->getPrevious());
        } finally {
            unset($this->resolvingDependencies[$id]);
        }
    }

    /**
     * @throws AutowiredExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DiDefinitionCallableExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function resolveDefinition(string $id): DiDefinitionAutowireInterface|DiDefinitionInterface
    {
        if (!isset($this->diResolvedDefinition[$id])) {
            $hasDefinition = \array_key_exists($id, $this->definitions);
            $isSingletonDefault = $this->config?->isSingletonServiceDefault() ?? false;

            if (!$hasDefinition) {
                try {
                    $reflectionClass = new \ReflectionClass($id);
                } catch (\ReflectionException) {
                    throw new NotFoundException("Definition identifier [{$id}] not found.");
                }

                if ($reflectionClass->isInterface()) {
                    if ($this->config?->isUseAttribute()) {
                        if ($service = $this->getServiceAttribute($reflectionClass)) {
                            return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire(
                                $service->getIdentifier(),
                                $service->isSingleton(),
                                $service->getArguments()
                            );
                        }

                        // @todo maybe recursion call 🚩
                        if (($serviceByReference = $this->getServiceByReferenceAttribute($reflectionClass))
                            && $definition = $this->resolveDefinition($serviceByReference->getIdentifier())) {
                            return $this->diResolvedDefinition[$serviceByReference->getIdentifier()] = $definition;
                        }
                    }

                    throw new NotFoundException('Definition not found for identifier '.$id);
                }

                if ($this->config?->isUseAttribute()
                    && $factory = $this->getDiFactoryAttribute($reflectionClass)->current()) {
                    return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire(
                        $factory->getIdentifier(),
                        $factory->isSingleton(),
                        $factory->getArguments()
                    );
                }

                return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire($reflectionClass, $isSingletonDefault, []);
            }

            $rawDefinition = $this->definitions[$id];

            if ($rawDefinition instanceof DiDefinitionReference) {
                $this->checkCyclicalDependencyCall($rawDefinition->getDefinition());
                $this->resolvingDependencies[$rawDefinition->getDefinition()] = true;

                try {
                    return $this->resolveDefinition($rawDefinition->getDefinition());
                } finally {
                    unset($this->resolvingDependencies[$rawDefinition->getDefinition()]);
                }
            }

            if (null === $rawDefinition) {
                return $this->diResolvedDefinition[$id] = new DiDefinitionValue($rawDefinition);
            }

            if ($rawDefinition instanceof DiDefinitionInterface) {
                return $this->diResolvedDefinition[$id] = $rawDefinition;
            }

            // @todo check it - maybe remove it from resolving?
            if (\is_callable($rawDefinition)) {
                return $this->diResolvedDefinition[$id] = new DiDefinitionCallable($rawDefinition, $isSingletonDefault, []);
            }

            return $this->diResolvedDefinition[$id] = new DiDefinitionValue($rawDefinition);
        }

        return $this->diResolvedDefinition[$id];
    }

    protected function checkCyclicalDependencyCall(string $id): void
    {
        if (\array_key_exists($id, $this->resolvingDependencies)) {
            $callPath = \implode(' -> ', \array_keys((array) $this->resolvingDependencies)).' -> '.$id;

            throw new CallCircularDependency('Trying call cyclical dependency. Call dependencies: '.$callPath);
        }
    }
}

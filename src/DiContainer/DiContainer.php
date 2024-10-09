<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @template T of object
 */
class DiContainer implements DiContainerInterface
{
    protected iterable $definitions = [];

    /**
     * @var DiContainerDefinition[]
     */
    protected iterable $definitionCache = [];
    protected array $resolved = [];

    /**
     * @param iterable<class-string|string, mixed|T> $definitions
     *
     * @throws ContainerExceptionInterface
     */
    public function __construct(
        iterable $definitions = [],
        protected ?DiContainerConfigInterface $config = null
    ) {
        foreach ($definitions as $id => $definition) {
            $key = \is_string($id) ? $id : $definition;
            $this->set(id: $key, definition: $definition);
        }
    }

    /**
     * @param class-string<T>|string $id
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
        return isset($this->definitions[$id])
            || isset($this->resolved[$id])
            || $this->hasClassOrInterface($id);
    }

    public function set(string $id, mixed $definition = null, ?array $arguments = null, ?bool $shared = null): static
    {
        if (isset($this->definitions[$id])) {
            throw new ContainerAlreadyRegisteredException("Key [{$id}] already registered in container.");
        }

        if (null === $definition) {
            $definition = $id;
        }

        $this->definitions[$id] = $definition;

        if ($arguments) {
            if (\is_array($this->definitions[$id])) {
                $arguments = $arguments + $this->definitions[$id][DiContainerInterface::ARGUMENTS] ?? [];
            }

            $this->definitions[$id] = [0 => $this->definitions[$id], DiContainerInterface::ARGUMENTS => $arguments];
        }

        if (null !== $shared) {
            if (\is_array($this->definitions[$id])) {
                $this->definitions[$id] += [DiContainerInterface::SHARED => $shared];
            } else {
                $this->definitions[$id] = [0 => $this->definitions[$id], DiContainerInterface::SHARED => $shared];
            }
        }

        return $this;
    }

    public function call(array|callable|string $definition, array $arguments = []): mixed
    {
        try {
            if (!\is_callable($definition)) {
                $definition = DefinitionAsCallable::makeFromAbstract($definition, $this);
            }

            $parameters = DefinitionAsCallable::reflectParameters($definition);
            $needToResolve = \array_filter(
                $parameters,
                static fn (\ReflectionParameter $parameter) => !isset($arguments[$parameter->getName()])
            );
            $resolvedArgs = $this->resolveInstanceArguments($needToResolve, []);

            return \call_user_func_array($definition, $arguments + $resolvedArgs);
        } catch (AutowiredExceptionInterface|DefinitionCallableExceptionInterface|NotFoundExceptionInterface|\ReflectionException $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Resolve dependencies.
     *
     * @param class-string<T> $id
     *
     * @return mixed|T
     *
     * @throws NotFoundExceptionInterface  no entry was found for **this** identifier
     * @throws ContainerExceptionInterface error while retrieving the entry
     */
    protected function resolve(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Unresolvable dependency [{$id}].");
        }

        $definition = $this->definitions[$id] ?? null;

        if ($this->config?->isUseAutowire()) {
            $diDefinition = $this->makeDefinition($id, $definition);

            try {
                if ($diDefinition->definition instanceof \Closure) {
                    $instance = $this->resolveInstance($diDefinition->definition, $diDefinition->arguments);

                    return $diDefinition->shared
                        ? $this->resolved[$id] = $instance
                        : $instance;
                }

                if (\is_string($diDefinition->definition)
                    && \is_a($diDefinition->definition, DiFactoryInterface::class, true)) {
                    $instance = $this->resolveInstance($diDefinition->definition, $diDefinition->arguments)($this);

                    return $diDefinition->shared
                        ? $this->resolved[$id] = $instance
                        : $instance;
                }

                if (\class_exists($diDefinition->id)) {
                    if ($this->config->isUseAttribute()
                        && $factory = DiFactory::makeFromReflection(new \ReflectionClass($diDefinition->id))) {
                        $factoryInstance = $this->resolveInstance($factory->id, $factory->arguments)($this);

                        return $factory->isShared
                            ? $this->resolved[$id] = $factoryInstance
                            : $factoryInstance;
                    }

                    $instance = $this->resolveInstance($diDefinition->id, $diDefinition->arguments);

                    return $diDefinition->shared
                        ? $this->resolved[$id] = $instance
                        : $instance;
                }

                if (\interface_exists($diDefinition->id)) {
                    if (null === $diDefinition->definition
                        && $this->config?->isUseAttribute()
                        && $service = Service::makeFromReflection(new \ReflectionClass($diDefinition->id))) {
                        $diDefinition->definition = $service->id;
                        $diDefinition->arguments = $service->arguments;
                        $diDefinition->shared = $service->isShared;
                    }

                    if (null === $diDefinition->definition) {
                        throw new ContainerException("Not found definition for interface [{$id}]");
                    }

                    if (isset($this->definitions[$diDefinition->definition])) { // merge argument definition
                        $diDefinition->arguments += $this->makeDefinition(
                            $diDefinition->definition,
                            $this->definitions[$diDefinition->definition]
                        )->arguments;
                    }

                    $instance = $this->resolveInstance($diDefinition->definition, $diDefinition->arguments);

                    return $diDefinition->shared
                        ? $this->resolved[$id] = $instance
                        : $instance;
                }
            } catch (AutowiredExceptionInterface|\ReflectionException $e) {
                throw new ContainerException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        return $this->resolved[$id] = $definition === $id
            ? $id
            : $this->getValue($definition);
    }

    protected function getValue(mixed $value): mixed
    {
        if (!\is_string($value)) {
            return $value;
        }

        if ($id = $this->config?->getReferenceToContainer($value)) {
            return $this->get($id);
        }

        return $this->has($value) ? $this->get($value) : $value;
    }

    protected function hasClassOrInterface(string $id): bool
    {
        return $this->config?->isUseAutowire()
            && $this->config?->isUseZeroConfigurationDefinition()
            && (\class_exists($id) || \interface_exists($id));
    }

    protected function makeDefinition(string $id, mixed $rawDefinition): DiContainerDefinition
    {
        if (!isset($this->definitionCache[$id])) {
            $sharedDefault = $this->config?->isSharedServiceDefault() ?? false;

            if ($rawDefinition instanceof \Closure) {
                return $this->definitionCache[$id] = new DiContainerDefinition($id, $rawDefinition, $sharedDefault);
            }

            if (\is_array($rawDefinition)) {
                return $this->definitionCache[$id] = new DiContainerDefinition(
                    $id,
                    $rawDefinition[0] ?? $id,
                    $rawDefinition[DiContainerInterface::SHARED] ?? $sharedDefault,
                    $rawDefinition[DiContainerInterface::ARGUMENTS] ?? []
                );
            }

            return $this->definitionCache[$id] = new DiContainerDefinition($id, $rawDefinition, $sharedDefault);
        }

        return $this->definitionCache[$id];
    }

    /**
     * @param class-string|\Closure $id
     */
    protected function resolveInstance(\Closure|string $id, array $arguments = []): mixed
    {
        try {
            if ($id instanceof \Closure) {
                $reflectionFunction = new \ReflectionFunction($id);
                $resolvedArgs = $this->resolveInstanceArguments($reflectionFunction->getParameters(), $arguments);

                return $reflectionFunction->invokeArgs($resolvedArgs);
            }

            ($reflectionClass = new \ReflectionClass($id))->isInstantiable()
                || throw new AutowiredException("The [{$id}] class is not instantiable");

            $parameters = $reflectionClass->getConstructor()?->getParameters() ?? [];
            $resolvedArgs = $this->resolveInstanceArguments($parameters, $arguments);

            return $reflectionClass->newInstanceArgs($resolvedArgs);
        } catch (\ReflectionException $e) {
            throw new AutowiredException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param \ReflectionParameter[] $parameters
     */
    protected function resolveInstanceArguments(array $parameters, array $arguments): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            if (isset($arguments[$parameter->name])) {
                $dependencies[$parameter->name] = $this->getValue($arguments[$parameter->name]);

                continue;
            }

            $parameterType = $parameter->getType();

            try {
                if (!$parameterType instanceof \ReflectionNamedType) {
                    throw new AutowiredException(
                        'Unsupported parameter type ['.($parameterType ?: 'no type')."] for name [{$parameter->name}]"
                    );
                }

                if ($this->config->isUseAttribute()) {
                    if ($factory = DiFactory::makeFromReflection($parameter)) {
                        try {
                            $this->set(id: $factory->id, arguments: $factory->arguments, shared: $factory->isShared);
                        } catch (ContainerAlreadyRegisteredException) {
                        }

                        $dependencies[$parameter->getName()] = $this->get($factory->id);

                        continue;
                    }

                    if ($inject = Inject::makeFromReflection($parameter)) {
                        $isInterface = \interface_exists($inject->id);

                        if ((!$isInterface && !\class_exists($inject->id)) || $parameterType->isBuiltin()) {
                            $dependencies[$parameter->getName()] = $this->get($inject->id);

                            continue;
                        }

                        if ($isInterface && $service = Service::makeFromReflection(new \ReflectionClass($inject->id))) {
                            try {
                                $this->set(id: $inject->id, definition: $service->id, arguments: $service->arguments, shared: $service->isShared);
                            } catch (ContainerAlreadyRegisteredException) {
                            }

                            $dependencies[$parameter->getName()] = $this->get($inject->id);

                            continue;
                        }

                        try {
                            $this->set(id: $inject->id, arguments: $inject->arguments, shared: $inject->isShared);
                        } catch (ContainerAlreadyRegisteredException) {
                        }

                        $dependencies[$parameter->getName()] = $this->get($inject->id);

                        continue;
                    }
                }

                $dependencies[$parameter->getName()] = match (true) {
                    $parameterType->isBuiltin() => $this->get($parameter->getName()),
                    ContainerInterface::class === $parameterType->getName() => $this,
                    default => $this->get($parameterType->getName()),
                };
            } catch (AutowiredExceptionInterface|ContainerExceptionInterface $e) {
                if (!$parameter->isDefaultValueAvailable()) {
                    $declaredClass = $parameter->getDeclaringClass()
                        ? $parameter->getDeclaringClass()->name.'::'
                        : '';
                    $where = $declaredClass.$parameter->getDeclaringFunction()->name;
                    $reason = $e->getMessage();

                    throw new AutowiredException("Unresolvable dependency [{$parameter}] in [{$where}]. Reason: {$reason}", $e->getCode(), $e);
                }

                $dependencies[$parameter->getName()] = $parameter->getDefaultValue();
            } catch (\ReflectionException $e) { // @codeCoverageIgnore
                throw new AutowiredException($e->getMessage(), $e->getCode(), $e); // @codeCoverageIgnore
            }
        }

        return $dependencies;
    }
}

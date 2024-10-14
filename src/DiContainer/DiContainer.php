<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;
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
     * @var <string, DiContainerDefinition|null>[]
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

    public function set(string $id, mixed $definition = null, ?array $arguments = null, ?bool $isSingleton = null): static
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
                $this->definitions[$id] = [DiContainerInterface::ARGUMENTS => $arguments] + $this->definitions[$id];
            } else {
                $this->definitions[$id] = [0 => $this->definitions[$id], DiContainerInterface::ARGUMENTS => $arguments];
            }
        }

        if (null !== $isSingleton) {
            $this->definitions[$id] = \is_array($this->definitions[$id])
                ? [DiContainerInterface::SINGLETON => $isSingleton] + $this->definitions[$id]
                : [0 => $this->definitions[$id], DiContainerInterface::SINGLETON => $isSingleton];
        }

        return $this;
    }

    public function call(array|callable|string $definition, array $arguments = []): mixed
    {
        try {
            $definition = DefinitionAsCallable::makeFromAbstract($definition, $this);

            $parameters = DefinitionAsCallable::reflectParameters($definition);
            $needToResolve = \array_filter(
                $parameters,
                static fn (\ReflectionParameter $parameter) => !isset($arguments[$parameter->getName()])
            );
            $resolvedArgs = $this->resolveInstanceArguments($needToResolve, []);

            return \call_user_func_array($definition, $arguments + $resolvedArgs);
        } catch (AutowiredExceptionInterface|DefinitionCallableExceptionInterface|NotFoundExceptionInterface $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Resolve dependencies.
     */
    protected function resolve(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Unresolvable dependency [{$id}].");
        }

        $definition = $this->definitions[$id] ?? null;

        try {
            if ($this->config?->isUseAutowire()
                && $diDefinition = $this->autowireDefinition($id, $definition)) {
                $object = ($o = $this->resolveInstance($diDefinition->definition, $diDefinition->arguments)) instanceof DiFactoryInterface
                    ? $o($this)
                    : $o;

                return $diDefinition->isSingleton
                    ? $this->resolved[$id] = $object
                    : $object;
            }
        } catch (AutowiredExceptionInterface $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $this->getValue($definition);
    }

    protected function getValue(mixed $value): mixed
    {
        return \is_string($value) && ($id = $this->config?->getReferenceToContainer($value))
            ? $this->get($id)
            : $value;
    }

    protected function hasClassOrInterface(string $id): bool
    {
        return $this->config?->isUseAutowire()
            && $this->config?->isUseZeroConfigurationDefinition()
            && (\class_exists($id) || \interface_exists($id));
    }

    protected function autowireDefinition(string $id, mixed $rawDefinition): ?DiContainerDefinition
    {
        if (!isset($this->definitionCache[$id])) {
            $isSingletonDefault = $this->config?->isSingletonServiceDefault() ?? false;
            $isIdInterface = \interface_exists($id);

            if (null === $rawDefinition) {
                if (\class_exists($id)) {
                    return $this->definitionCache[$id] = $this->config?->isUseAttribute()
                        && ($factory = DiFactory::makeFromReflection(new \ReflectionClass($id)))
                            ? new DiContainerDefinition($id, $factory->id, $factory->isSingleton, $factory->arguments)
                            : new DiContainerDefinition($id, $id, $isSingletonDefault, []);
                }

                if ($isIdInterface && $this->config?->isUseAttribute()
                    && $service = Service::makeFromReflection(new \ReflectionClass($id))) {
                    return $this->definitionCache[$id] = new DiContainerDefinition($id, $service->id, $service->isSingleton, $service->arguments);
                }
            }

            if (\is_array($rawDefinition)) {
                $definition = $rawDefinition[0] ?? $id;
                $isSingleton = $rawDefinition[DiContainerInterface::SINGLETON] ?? $isSingletonDefault;
                $arguments = $rawDefinition[DiContainerInterface::ARGUMENTS] ?? [];
            } else {
                $definition = $rawDefinition;
                $isSingleton = $isSingletonDefault;
                $arguments = [];
            }

            if ($definition instanceof \Closure) {
                return $this->definitionCache[$id] = new DiContainerDefinition($id, $definition, $isSingleton, $arguments);
            }

            if (\is_string($definition) && (\class_exists($definition) || $isIdInterface)) {
                if ($isIdInterface && [] === $arguments && isset($this->definitions[$definition])) {
                    $arguments = $this->autowireDefinition($definition, $this->definitions[$definition])->arguments;
                }

                return $this->definitionCache[$id] = new DiContainerDefinition($id, $definition, $isSingleton, $arguments);
            }

            return $this->definitionCache[$id] = null;
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
                    throw new AutowiredException('Unsupported parameter type ['.($parameterType ?: 'no type').'].');
                }

                if ($this->config->isUseAttribute()) {
                    if ($factory = DiFactory::makeFromReflection($parameter)) {
                        $dependencyKey = $this->registerDefinition($parameter, $factory->id, $factory->arguments, $factory->isSingleton);
                        $dependencies[$parameter->getName()] = $this->get($dependencyKey);

                        continue;
                    }

                    if ($inject = Inject::makeFromReflection($parameter)) {
                        $isInterface = \interface_exists($inject->id);

                        if ((!$isInterface && !\class_exists($inject->id)) || $parameterType->isBuiltin()) {
                            $dependencies[$parameter->getName()] = $this->getValue($inject->id);

                            continue;
                        }

                        if ($isInterface) {
                            $service = Service::makeFromReflection(new \ReflectionClass($inject->id))
                                ?: throw new AutowiredException(
                                    "The interface [{$inject->id}] is not defined via the php-attribute like #[Service]."
                                );
                            $dependencyKey = $this->registerDefinition($parameter, $service->id, $service->arguments, $service->isSingleton);
                            $dependencies[$parameter->getName()] = $this->get($dependencyKey);

                            continue;
                        }

                        $dependencyKey = $this->registerDefinition($parameter, $inject->id, $inject->arguments, $inject->isSingleton);
                        $dependencies[$parameter->getName()] = $this->get($dependencyKey);

                        continue;
                    }
                }

                $dependencies[$parameter->getName()] = match (true) {
                    $parameterType->isBuiltin() => $this->get($parameter->getName()),
                    ContainerInterface::class === $parameterType->getName() => $this,
                    default => $this->get($parameterType->getName()),
                };
            } catch (AutowiredExceptionInterface|ContainerExceptionInterface $e) {
                if ($e instanceof AutowiredAttributeException || !$parameter->isDefaultValueAvailable()) {
                    $declaredClass = $parameter->getDeclaringClass() ? $parameter->getDeclaringClass()->getName().'::' : '';
                    $where = $declaredClass.$parameter->getDeclaringFunction()->getName().', parameter position #'.$parameter->getPosition();
                    $messageParameter = "The parameter \"{$parameter->getName()}\" in {$where}";
                    $message = "Unresolvable dependency. {$messageParameter}. Reason: {$e->getMessage()}";

                    throw new AutowiredException($message, $e->getCode(), $e);
                }

                $dependencies[$parameter->getName()] = $parameter->getDefaultValue();
            }
        }

        return $dependencies;
    }

    protected function registerDefinition(\ReflectionParameter $parameter, mixed $definition, array $arguments, bool $isSingleton): string
    {
        $fnName = $parameter->getDeclaringFunction();
        $target = $parameter->getDeclaringClass()?->getName() ?: $fnName->getName().$fnName->getStartLine();
        $dependencyKey = $target.'::'.$fnName->getName().'::'.$parameter->getType().':'.$parameter->getPosition();

        try {
            $this->set(id: $dependencyKey, definition: $definition, arguments: $arguments, isSingleton: $isSingleton);
        } catch (ContainerAlreadyRegisteredException) {
        }

        return $dependencyKey;
    }
}

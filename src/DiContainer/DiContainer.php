<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @template T of object
 */
class DiContainer implements DiContainerInterface, DiContainerSetterInterface, DiContainerCallInterface
{
    protected iterable $definitions = [];

    /**
     * @var iterable<string, null|DiContainerDefinition>
     */
    protected iterable $diAutowireDefinition = [];
    protected iterable $resolved = [];
    protected iterable $checkCircularResolvingDependencies = [];

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
            $key = \is_string($id) ? $id : (string) $definition;
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
            || ($this->config?->isUseZeroConfigurationDefinition() && (\class_exists($id) || \interface_exists($id)));
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
            throw new ContainerException(message: $e->getMessage(), previous: $e);
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

        if (isset($this->checkCircularResolvingDependencies[$id])) {
            $callPath = \implode(' -> ', \array_keys((array) $this->checkCircularResolvingDependencies));

            throw new CallCircularDependency('Trying call cyclical dependency. Call dependencies: '.$callPath);
        }

        $this->checkCircularResolvingDependencies[$id] = true;

        try {
            if ($this->config?->isUseAutowire()
                && $diAutowireDefinition = $this->autowireDefinition($id, $definition)) {
                $object = ($o = $this->resolveInstance($diAutowireDefinition->definition, $diAutowireDefinition->arguments)) instanceof DiFactoryInterface
                    ? $o($this)
                    : $o;

                return $diAutowireDefinition->isSingleton
                    ? $this->resolved[$diAutowireDefinition->id] = $object
                    : $object;
            }

            return $this->getValue($definition);
        } catch (AutowiredExceptionInterface $e) {
            throw new ContainerException(message: $e->getMessage(), previous: $e->getPrevious());
        } finally {
            unset($this->checkCircularResolvingDependencies[$id]);
        }
    }

    protected function getValue(mixed $value): mixed
    {
        return \is_string($value) && ($id = $this->config?->getReferenceToContainer($value))
            ? $this->get($id)
            : $value;
    }

    protected function autowireDefinition(string $id, mixed $rawDefinition): ?DiContainerDefinition
    {
        if (!isset($this->diAutowireDefinition[$id])) {
            $isSingletonDefault = $this->config?->isSingletonServiceDefault() ?? false;
            $isIdInterface = \interface_exists($id);

            if (null === $rawDefinition) {
                if (\class_exists($id)) {
                    return $this->diAutowireDefinition[$id] = $this->config?->isUseAttribute()
                        && ($factory = DiFactory::makeFromReflection(new \ReflectionClass($id)))
                            ? new DiContainerDefinition($id, $factory->id, $factory->isSingleton, $factory->arguments)
                            : new DiContainerDefinition($id, $id, $isSingletonDefault, []);
                }

                if ($isIdInterface && $this->config?->isUseAttribute()
                    && $service = Service::makeFromReflection(new \ReflectionClass($id))) {
                    return $this->diAutowireDefinition[$id] = new DiContainerDefinition($id, $service->id, $service->isSingleton, $service->arguments);
                }

                throw new NotFoundException('Definition not found for '.$id);
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
                return $this->diAutowireDefinition[$id] = new DiContainerDefinition($id, $definition, $isSingleton, $arguments);
            }

            if (\is_string($definition) && (\class_exists($definition) || $isIdInterface)) {
                if ($isIdInterface && [] === $arguments && isset($this->definitions[$definition][DiContainerInterface::ARGUMENTS])) {
                    $arguments += (array) $this->definitions[$definition][DiContainerInterface::ARGUMENTS];
                }

                return $this->diAutowireDefinition[$id] = new DiContainerDefinition($id, $definition, $isSingleton, $arguments);
            }

            return $this->diAutowireDefinition[$id] = null;
        }

        return $this->diAutowireDefinition[$id];
    }

    /**
     * @param class-string|\Closure $definition
     */
    protected function resolveInstance(\Closure|string $definition, array $arguments = []): mixed
    {
        try {
            if ($definition instanceof \Closure) {
                $reflectionFunction = new \ReflectionFunction($definition);
                $resolvedArgs = $this->resolveInstanceArguments($reflectionFunction->getParameters(), $arguments);

                return $reflectionFunction->invokeArgs($resolvedArgs);
            }

            ($reflectionClass = new \ReflectionClass($definition))->isInstantiable()
                || throw new AutowiredException("The [{$definition}] class is not instantiable");

            $parameters = $reflectionClass->getConstructor()?->getParameters() ?? [];
            $resolvedArgs = $this->resolveInstanceArguments($parameters, $arguments);

            return $reflectionClass->newInstanceArgs($resolvedArgs);
        } catch (\ReflectionException $e) {
            throw new AutowiredException(message: $e->getMessage(), previous: $e->getPrevious());
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

            try {
                $parameterType = $this->getParameterType($parameter);

                if ($this->config?->isUseAttribute()) {
                    if ($factory = DiFactory::makeFromReflection($parameter)) {
                        $dependencyKey = $this->registerDefinition($parameter, $factory->id, $factory->arguments, $factory->isSingleton);
                        $dependencies[$parameter->getName()] = $this->get($dependencyKey);

                        continue;
                    }

                    if ($inject = Inject::makeFromReflection($parameter)) {
                        $id = (string) $inject->id;
                        $isInterface = \interface_exists($id);

                        if (!$isInterface && !\class_exists($id)) {
                            $dependencies[$parameter->getName()] = $this->getValue($id);

                            continue;
                        }

                        if ($isInterface) {
                            $service = Service::makeFromReflection(new \ReflectionClass($id))
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
                    ContainerInterface::class === $parameterType?->getName() => $this,
                    null !== $parameterType && !$parameterType->isBuiltin() => $this->get($parameterType->getName()),
                    default => $this->get($parameter->getName()),
                };
            } catch (CallCircularDependency $e) {
                throw $e;
            } catch (AutowiredExceptionInterface|ContainerExceptionInterface $e) {
                if ($e instanceof AutowiredAttributeException || !$parameter->isDefaultValueAvailable()) {
                    $declaredClass = $parameter->getDeclaringClass()?->getName() ?: '';
                    $declaredFunction = $parameter->getDeclaringFunction()->getName();
                    $where = \implode('::', \array_filter([$declaredClass, $declaredFunction]));
                    $messageParameter = $parameter.' in '.$where;
                    $message = "Unresolvable dependency. {$messageParameter}. Reason: {$e->getMessage()}";

                    throw new AutowiredException(message: $message, previous: $e);
                }

                $dependencies[$parameter->getName()] = $parameter->getDefaultValue();
            }
        }

        return $dependencies;
    }

    protected function getParameterType(\ReflectionParameter $parameter): ?\ReflectionNamedType
    {
        return ($t = $parameter->getType()) instanceof \ReflectionUnionType
            ? $t->getTypes()[0] // Get first union type e.g. __construct(Class1|Class2 $dependency) will return 'Class1'
            : $parameter->getType();
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

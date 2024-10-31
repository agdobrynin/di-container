<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionSimple;
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
use Kaspi\DiContainer\Interfaces\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Kaspi\DiContainer\Function\getParameterType;

/**
 * @template T of object
 */
class DiContainer implements DiContainerInterface, DiContainerSetterInterface, DiContainerCallInterface
{
    protected iterable $definitions = [];

    /**
     * @var iterable<string, DiDefinitionAutowireInterface|DiDefinitionInterface>
     */
    protected iterable $diResolvedDefinition = [];
    protected iterable $resolved = [];
    protected iterable $resolvingDependencies = [];

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
            $f = static fn (\ReflectionParameter $parameter) => !isset($arguments[$parameter->getName()]);
            $needToResolve = \array_filter($parameters, $f);
            $resolvedArgs = $this->resolveInstanceArguments($needToResolve);

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

        $this->checkCyclicalDependencyCall($id);
        $this->resolvingDependencies[$id] = true;

        try {
            $diDefinition = $this->resolveDefinition($id);

            if ($diDefinition instanceof DiDefinitionAutowireInterface) {
                $resolvedArgs = $this->resolveInstanceArguments($diDefinition->getArgumentsForResolving());
                $object = ($o = $diDefinition->invoke($resolvedArgs)) instanceof DiFactoryInterface
                    ? $o($this)
                    : $o;

                return $diDefinition->isSingleton()
                    ? $this->resolved[$diDefinition->getContainerId()] = $object
                    : $object;
            }

            return $this->resolved[$diDefinition->getContainerId()] = $diDefinition->getDefinition();
        } catch (AutowiredExceptionInterface|\ReflectionException $e) {
            throw new ContainerException(message: $e->getMessage(), previous: $e->getPrevious());
        } finally {
            unset($this->resolvingDependencies[$id]);
        }
    }

    protected function resolveDefinition(string $id): DiDefinitionAutowireInterface|DiDefinitionInterface
    {
        if (!isset($this->diResolvedDefinition[$id])) {
            $rawDefinition = $this->definitions[$id] ?? null;

            if (!$this->config?->isUseAutowire()) {
                return $this->diResolvedDefinition[$id] = new DiDefinitionSimple($id, $rawDefinition);
            }

            if (\is_a($id, ContainerInterface::class, true)) {
                // @phan-suppress-next-line PhanUnreferencedClosure
                return new DiDefinitionClosure($id, fn () => $this, true, []);
            }

            $isSingletonDefault = $this->config?->isSingletonServiceDefault() ?? false;
            $isIdInterface = \interface_exists($id);

            if (null === $rawDefinition) {
                if (\class_exists($id)) {
                    return $this->diResolvedDefinition[$id] = $this->config?->isUseAttribute()
                        && ($factory = DiFactory::makeFromReflection(new \ReflectionClass($id)))
                            ? new DiDefinitionAutowire($id, $factory->id, $factory->isSingleton, $factory->arguments)
                            : new DiDefinitionAutowire($id, $id, $isSingletonDefault, []);
                }

                if ($isIdInterface && $this->config?->isUseAttribute()
                    && $service = Service::makeFromReflection(new \ReflectionClass($id))) {
                    return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire($id, $service->id, $service->isSingleton, $service->arguments);
                }

                throw new NotFoundException('Definition not found for '.$id);
            }

            if (\is_array($rawDefinition)) {
                $definition = $rawDefinition[0] ?? $id;
                $isSingleton = (bool) ($rawDefinition[DiContainerInterface::SINGLETON] ?? $isSingletonDefault);
                $arguments = (array) ($rawDefinition[DiContainerInterface::ARGUMENTS] ?? []);
            } else {
                $definition = $rawDefinition;
                $isSingleton = $isSingletonDefault;
                $arguments = [];
            }

            if ($definition instanceof \Closure) {
                return $this->diResolvedDefinition[$id] = new DiDefinitionClosure($id, $definition, $isSingleton, $arguments);
            }

            if (\is_string($definition) && (\class_exists($definition) || $isIdInterface)) {
                if ($isIdInterface && [] === $arguments && isset($this->definitions[$definition][DiContainerInterface::ARGUMENTS])) {
                    $arguments += (array) $this->definitions[$definition][DiContainerInterface::ARGUMENTS];
                }

                return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire($id, $definition, $isSingleton, $arguments);
            }

            if (\is_string($rawDefinition) && $ref = $this->config?->getReferenceToContainer($rawDefinition)) {
                $this->checkCyclicalDependencyCall($ref);

                return $this->resolveDefinition($ref);
            }

            return $this->diResolvedDefinition[$id] = new DiDefinitionSimple($id, $rawDefinition);
        }

        return $this->diResolvedDefinition[$id];
    }

    /**
     * @param array<int, mixed|\ReflectionParameter> $parameters
     */
    protected function resolveInstanceArguments(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $name => $parameter) {
            if (!$parameter instanceof \ReflectionParameter) {
                $dependencies[$name] = \is_string($parameter) && ($ref = $this->config?->getReferenceToContainer($parameter))
                    ? $this->get($ref)
                    : $parameter;

                continue;
            }

            try {
                if ($this->config?->isUseAttribute()) {
                    if ($factory = DiFactory::makeFromReflection($parameter)) {
                        $dependencyKey = $this->registerDefinition($parameter, $factory->id, $factory->arguments, $factory->isSingleton);
                        $dependencies[$parameter->getName()] = $this->get($dependencyKey);

                        continue;
                    }

                    if ($inject = Inject::makeFromReflection($parameter, $this)) {
                        $injectDefinition = (string) $inject->id;
                        $isInterface = \interface_exists($injectDefinition);

                        if (!$isInterface && !\class_exists($injectDefinition)) {
                            $dependencies[$parameter->getName()] = ($ref = $this->config?->getReferenceToContainer($injectDefinition))
                                ? $this->get($ref)
                                : $this->get($parameter->getName());

                            continue;
                        }

                        if ($isInterface) {
                            $service = Service::makeFromReflection(new \ReflectionClass($injectDefinition))
                                ?: throw new AutowiredException(
                                    "The interface [{$injectDefinition}] is not defined via the php-attribute like #[Service]."
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

                $parameterType = getParameterType($parameter, $this);

                $dependencies[$parameter->getName()] = null === $parameterType
                    ? $this->get($parameter->getName())
                    : $this->get($parameterType->getName());
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

    protected function checkCyclicalDependencyCall(string $id): void
    {
        if (isset($this->resolvingDependencies[$id])) {
            $callPath = \implode(' -> ', \array_keys((array) $this->resolvingDependencies)).' -> '.$id;

            throw new CallCircularDependency('Trying call cyclical dependency. Call dependencies: '.$callPath);
        }
    }
}

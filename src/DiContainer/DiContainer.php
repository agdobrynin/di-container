<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DiDefinition\CallableParserTrait;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionSimple;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @template T of object
 */
class DiContainer implements DiContainerInterface, DiContainerCallInterface
{
    use ParameterTypeResolverTrait;
    use CallableParserTrait;

    protected array $definitions = [];

    /**
     * @var iterable<string, DiDefinitionAutowireInterface|DiDefinitionInterface>
     */
    protected array $diResolvedDefinition = [];
    protected array $resolved = [];
    protected array $resolvingDependencies = [];

    /**
     * @param iterable<class-string|string, mixed|T> $definitions
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
        return \array_key_exists($id, $this->definitions)
            || \array_key_exists($id, $this->resolved)
            || (
                $this->config?->isUseZeroConfigurationDefinition()
                && (\class_exists($id) || \interface_exists($id) || \is_callable($id))
            );
    }

    public function set(string $id, mixed $definition, ?array $arguments = null, ?bool $isSingleton = null): static
    {
        if (\array_key_exists($id, $this->definitions)) {
            throw new ContainerAlreadyRegisteredException("Key [{$id}] already registered in container.");
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
            $callable = $this::parseCallable($definition, $this);

            return (new DiDefinitionCallable('#EMPTY#', $callable, false, $arguments))
                ->invoke($this, $this->config?->isUseAttribute())
            ;
        } catch (AutowiredExceptionInterface|DiDefinitionCallableExceptionInterface $e) {
            throw new ContainerException(message: $e->getMessage(), previous: $e);
        }
    }

    /**
     * Resolve dependencies.
     *
     * @throws ContainerExceptionInterface
     */
    protected function resolve(string $id): mixed
    {
        if ($ref = $this->config?->getReferenceToContainer($id)) {
            return $this->get($ref);
        }

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
                $object = ($o = $diDefinition->invoke($this, $this->config?->isUseAttribute())) instanceof DiFactoryInterface
                    ? $o($this)
                    : $o;

                return $diDefinition->isSingleton()
                    ? $this->resolved[$diDefinition->getContainerId()] = $object
                    : $object;
            }

            return $this->resolved[$diDefinition->getContainerId()] = $diDefinition->getDefinition();
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
            $rawDefinition = $this->definitions[$id] ?? null;

            if ($hasDefinition && \is_string($rawDefinition) && $ref = $this->config?->getReferenceToContainer($rawDefinition)) {
                $this->checkCyclicalDependencyCall($ref);
                $this->resolvingDependencies[$ref] = true;

                return $this->resolveDefinition($ref);
            }

            if (($hasDefinition && null === $rawDefinition) || !$this->config?->isUseAutowire()) {
                return $this->diResolvedDefinition[$id] = new DiDefinitionSimple($id, $rawDefinition);
            }

            $isSingletonDefault = $this->config?->isSingletonServiceDefault() ?? false;

            if (null === $rawDefinition) {
                if (\is_callable($id)) {
                    return $this->diResolvedDefinition[$id] = new DiDefinitionCallable($id, $id, $isSingletonDefault, []);
                }

                if (\class_exists($id)) {
                    $reflectionClass = new \ReflectionClass($id);

                    return $this->diResolvedDefinition[$id] = $this->config?->isUseAttribute()
                        && ($factories = DiFactory::makeFromReflection($reflectionClass))
                            ? new DiDefinitionAutowire($id, $factories[0]->id, $factories[0]->isSingleton, $factories[0]->arguments)
                            : new DiDefinitionAutowire($id, $id, $isSingletonDefault, []);
                }

                if (\interface_exists($id) && $this->config?->isUseAttribute()) {
                    $reflectionInterface = new \ReflectionClass($id);
                    $service = Service::makeFromReflection($reflectionInterface)
                        ?: throw new NotFoundException('Definition not found for '.$id);

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

            $isIdInterface = \interface_exists($id);

            if (\is_string($definition) && (\class_exists($definition) || $isIdInterface)) {
                if ($isIdInterface && [] === $arguments && isset($this->definitions[$definition][DiContainerInterface::ARGUMENTS])) {
                    $arguments += (array) $this->definitions[$definition][DiContainerInterface::ARGUMENTS];
                }

                return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire($id, $definition, $isSingleton, $arguments);
            }

            if (\is_callable($definition)) {
                return $this->diResolvedDefinition[$id] = new DiDefinitionCallable($id, $definition, $isSingleton, $arguments);
            }

            return $this->diResolvedDefinition[$id] = new DiDefinitionSimple($id, $rawDefinition);
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

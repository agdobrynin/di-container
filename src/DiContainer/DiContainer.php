<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionRuntimeInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedObjectDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceDefinitionsMutableInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function array_key_exists;
use function array_keys;
use function array_map;
use function call_user_func_array;
use function class_exists;
use function count;
use function implode;
use function interface_exists;
use function is_callable;
use function is_string;
use function sprintf;
use function var_export;

/**
 * @phpstan-import-type NotParsedCallable from DiContainerCallInterface
 * @phpstan-import-type ParsedCallable from DiContainerCallInterface
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 */
class DiContainer implements DiContainerInterface, DiContainerSetterInterface, DiContainerCallInterface
{
    protected readonly SourceDefinitionsMutableInterface $definitions;

    protected readonly SourceParametersMutableInterface $parameters;

    /**
     * @var array<class-string|string, DiDefinitionInterface>
     */
    protected array $diResolvedDefinition = [];

    /**
     * @var array<class-string|string, mixed>
     */
    protected array $resolved = [];

    /**
     * Watch circular call.
     *
     * @var array<class-string|string, true>
     */
    protected array $circularCallWatcher = [];

    /**
     * Memorizing a class or interface using zero-definition configuration.
     *
     * @see DiContainerConfigInterface::isUseZeroConfigurationDefinition()
     *
     * @var array<class-string|string, bool>
     */
    protected array $hasViaZeroConfig = [];

    /**
     * @var array<class-string, true>
     */
    protected readonly array $containerIds;

    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     * @param iterable<class-string|non-empty-string, mixed>                                     $removedDefinitionIds
     * @param iterable<non-empty-string, SourceParameterType>|SourceParametersMutableInterface   $parameters
     *
     * @throws ContainerIdentifierExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function __construct(
        iterable|SourceDefinitionsMutableInterface $definitions = [],
        protected DiContainerConfigInterface $config = new DiContainerNullConfig(),
        iterable $removedDefinitionIds = [],
        iterable|SourceParametersMutableInterface $parameters = [],
    ) {
        $this->definitions = !($definitions instanceof SourceDefinitionsMutableInterface)
            ? new ImmediateSourceDefinitionsMutable($definitions, $removedDefinitionIds)
            : $definitions;
        $this->parameters = !($parameters instanceof SourceParametersMutableInterface)
            ? new ImmediateSourceParameters($parameters)
            : $parameters;
        $this->containerIds = [ContainerInterface::class => true, DiContainerInterface::class => true, __CLASS__ => true];
    }

    public function get(string $id): mixed
    {
        return array_key_exists($id, $this->resolved)
            ? $this->resolved[$id]
            : $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return $this->definitions->has($id)
            || array_key_exists($id, $this->resolved)
            || isset($this->containerIds[$id])
            || $this->hasViaZeroConfigurationDefinition($id);
    }

    public function set(string $id, mixed $definition): static
    {
        if (isset($this->containerIds[$id])) {
            throw new ContainerAlreadyRegisteredException(id: $id);
        }

        $this->definitions->set($id, $definition);

        return $this;
    }

    public function call(array|callable|string $definition, mixed ...$argument): mixed
    {
        $reflectionDefinition = DefinitionDiCall::getReflection($definition);

        /**
         * @phpstan-var array<non-empty-string|non-negative-int, mixed> $argument
         */
        if ($reflectionDefinition instanceof ReflectionMethod) {
            if ($reflectionDefinition->isStatic()) {
                return call_user_func_array(
                    [$reflectionDefinition->class, $reflectionDefinition->name], // @phpstan-ignore argument.type
                    ArgumentResolver::resolve(new ArgumentBuilder($argument, $reflectionDefinition, $this), $this)
                );
            }

            $class = $reflectionDefinition->objectOrClassName;

            if (is_string($class)) {
                try {
                    $class = $this->get($class);
                } catch (ContainerExceptionInterface $e) {
                    throw new DiDefinitionException(
                        message: sprintf('Cannot get entry via container identifier "%s" for create callable definition.', $class),
                        previous: $e
                    );
                }
            }

            if (!is_callable($callable = [$class, $reflectionDefinition->name])) {
                throw new DiDefinitionException(sprintf('Cannot create callable from %s.', var_export($callable, true)));
            }

            return call_user_func_array(
                $callable,
                ArgumentResolver::resolve(new ArgumentBuilder($argument, $reflectionDefinition, $this), $this)
            );
        }

        return call_user_func_array(
            $definition, // @phpstan-ignore argument.type
            ArgumentResolver::resolve(new ArgumentBuilder($argument, $reflectionDefinition, $this), $this)
        );
    }

    /**
     * @return iterable<class-string|non-empty-string, DiDefinitionRuntimeInterface|DiDefinitionType>
     */
    public function getDefinitions(): iterable
    {
        foreach ($this->definitions->getIterator() as $id => $definition) {
            if (!isset($this->containerIds[$id])) {
                yield $id => $definition;
            }
        }
    }

    public function getConfig(): DiContainerConfigInterface
    {
        return $this->config;
    }

    public function findTaggedDefinitions(string $tag): iterable
    {
        $tagIsInterface = null;

        foreach ($this->definitions->getIterator() as $containerIdentifier => $definition) {
            if (!$definition instanceof DiTaggedDefinitionInterface) {
                continue;
            }

            $hasTagAsInterface = false;

            if ($definition instanceof DiTaggedObjectDefinitionInterface) {
                $tagIsInterface ??= interface_exists($tag);
                // Pass container with configuration for determinate using php attribute or not.
                $definition->setContainer($this);
                $hasTagAsInterface = $tagIsInterface && $definition->isImplementInterface($tag);
            }

            if ($hasTagAsInterface || (true !== $tagIsInterface && $definition->hasTag($tag))) {
                yield $containerIdentifier => $definition;
            }
        }
    }

    /**
     * @return (DiDefinitionInterface&DiDefinitionType)|DiDefinitionRuntimeInterface
     */
    public function getDefinition(string $id): DiDefinitionInterface
    {
        if (isset($this->containerIds[$id]) || !$this->has($id)) {
            throw new NotFoundException(id: $id);
        }

        try {
            return $this->resolveDefinition($id);
        } catch (AutowireExceptionInterface $e) {
            throw new ContainerException(
                sprintf('Cannot create definition via container identifier %s.', var_export($id, true)),
                previous: $e
            );
        }
    }

    public function getRemovedDefinitionIds(): iterable
    {
        yield from $this->definitions->getRemovedDefinitionIds();
    }

    public function parameters(): SourceParametersMutableInterface
    {
        return $this->parameters;
    }

    /**
     * Resolve dependencies.
     *
     * @param class-string|string $id
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    protected function resolve(string $id): mixed
    {
        try {
            if (isset($this->containerIds[$id])) {
                return $this;
            }

            if (!$this->has($id)) {
                throw new NotFoundException($this->getMessageChainResolving($id), id: $id);
            }

            $this->checkCyclicalDependencyCall($id);
            $this->circularCallWatcher[$id] = true;

            $diDefinition = $this->resolveDefinition($id);

            if ($diDefinition instanceof DiDefinitionLinkInterface) {
                $this->resolve($diDefinition->getDefinition());
            }

            $resolvedEntry = $diDefinition->resolve($this, $this);

            if ($diDefinition instanceof DiDefinitionSingletonInterface
                && !($diDefinition->isSingleton() ?? $this->config->isSingletonServiceDefault())) {
                return $resolvedEntry;
            }

            return $this->resolved[$id] = $resolvedEntry;
        } finally {
            unset($this->circularCallWatcher[$id]);
        }
    }

    /**
     * @param class-string|string $id
     *
     * @throws AutowireException|NotFoundException
     */
    protected function resolveDefinition(string $id): DiDefinitionAutowireInterface|DiDefinitionInterface|DiDefinitionLinkInterface|DiDefinitionSingletonInterface|DiDefinitionTaggedAsInterface
    {
        $sourceDefinition = $this->definitions->get($id);

        if (null !== $sourceDefinition) {
            return $sourceDefinition;
        }

        if (isset($this->diResolvedDefinition[$id])) {
            return $this->diResolvedDefinition[$id];
        }

        try {
            $reflectionClass = new ReflectionClass($id); // @phpstan-ignore argument.type
        } catch (ReflectionException $e) {
            throw new NotFoundException($this->getMessageChainResolving($id), previous: $e, id: $id);
        }

        if ($reflectionClass->isInterface()) {
            if ($this->config->isUseAttribute()
                && null !== ($service = AttributeReader::getServiceAttribute($reflectionClass))) {
                $findId = $service->id;

                try {
                    do {
                        $this->circularCallWatcher[$findId] = true;
                        $definition = $this->resolveDefinition($findId);

                        if ($definition instanceof DiDefinitionLinkInterface) {
                            $findId = $definition->getDefinition();
                            $this->checkCyclicalDependencyCall($findId);
                        }
                    } while ($definition instanceof DiDefinitionLinkInterface);
                } finally {
                    unset($this->circularCallWatcher[$findId]);
                }

                return $this->diResolvedDefinition[$id] = $this->getDiDefinitionWrapper($definition, $service->isSingleton);
            }

            throw new AutowireException(sprintf('Attempting to resolve interface "%s". An interface that is not bound to a definition.', $id));
        }

        if ($this->config->isUseAttribute()) {
            if (null !== ($factory = AttributeReader::getDiFactoryAttributeOnClass($reflectionClass))) {
                $diFactory = new DiDefinitionFactory($factory->definition, $factory->isSingleton);

                return $this->diResolvedDefinition[$id] = $diFactory->bindArguments(...$factory->arguments);
            }

            if (($autowires = AttributeReader::getAutowireAttribute($reflectionClass))->valid()) {
                foreach ($autowires as $autowire) {
                    if ('' === $autowire->id || $autowire->id === $reflectionClass->name) {
                        return $this->diResolvedDefinition[$id] = (new DiDefinitionAutowire($reflectionClass, $autowire->isSingleton))
                            ->bindArguments(...$autowire->arguments)
                        ;
                    }
                }
            }

            if (($diRuntimes = AttributeReader::getDiRuntimeAttribute($reflectionClass))->valid()) {
                foreach ($diRuntimes as $diRuntime) {
                    if ('' === $diRuntime->containerIdentifier || $diRuntime->containerIdentifier === $reflectionClass->name) {
                        return $this->diResolvedDefinition[$id] = new DiDefinitionRuntime($reflectionClass->name, $diRuntime->message);
                    }
                }
            }
        }

        return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire($reflectionClass, $this->config->isSingletonServiceDefault());
    }

    protected function getMessageChainResolving(string $currentResolveId): string
    {
        if (1 < count($this->circularCallWatcher)) {
            $chainIds = array_map(
                static fn (string $i) => var_export($i, true),
                [...array_keys($this->circularCallWatcher), ...[$currentResolveId]]
            );

            return sprintf('Chain resolving container identifiers: %s.', implode(' -> ', $chainIds));
        }

        return '';
    }

    protected function hasViaZeroConfigurationDefinition(string $id): bool
    {
        if (!$this->config->isUseZeroConfigurationDefinition()) {
            return false;
        }

        if (isset($this->hasViaZeroConfig[$id])) {
            return $this->hasViaZeroConfig[$id];
        }

        if ($this->definitions->isRemovedDefinition($id)) { // @phpstan-ignore argument.type
            return false;
        }

        if (class_exists($id) || interface_exists($id)) {
            return $this->hasViaZeroConfig[$id] = (!$this->config->isUseAttribute()
                || !AttributeReader::isAutowireExclude(new ReflectionClass($id)));
        }

        return $this->hasViaZeroConfig[$id] = false;
    }

    protected function checkCyclicalDependencyCall(string $id): void
    {
        if (array_key_exists($id, $this->circularCallWatcher)) {
            throw new CallCircularDependencyException(callIds: [...array_keys($this->circularCallWatcher), $id]);
        }
    }

    private function getDiDefinitionWrapper(DiDefinitionAutowireInterface|DiDefinitionInterface $definition, ?bool $singleton): DiDefinitionSingletonInterface
    {
        return new class($definition, $singleton) implements DiDefinitionSingletonInterface {
            public function __construct(
                private readonly DiDefinitionAutowireInterface|DiDefinitionInterface $definition,
                private readonly ?bool $isSingleton
            ) {}

            public function getDefinition(): DiDefinitionAutowireInterface|DiDefinitionInterface
            {
                return $this->definition; // @codeCoverageIgnore
            }

            public function resolve(DiContainerInterface $container, mixed $context = null): mixed
            {
                return $this->definition->resolve($container, $context);
            }

            public function isSingleton(): ?bool
            {
                return $this->isSingleton;
            }
        };
    }
}

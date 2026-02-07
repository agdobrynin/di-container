<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\RemovedDefinitionIdsInterface;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceDefinitionsMutableInterface;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function array_key_exists;
use function array_keys;
use function call_user_func_array;
use function class_exists;
use function in_array;
use function interface_exists;
use function is_callable;
use function is_string;
use function sprintf;
use function var_export;

/**
 * @phpstan-import-type NotParsedCallable from DiContainerCallInterface
 * @phpstan-import-type ParsedCallable from DiContainerCallInterface
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 */
class DiContainer implements DiContainerInterface, DiContainerSetterInterface, DiContainerCallInterface
{
    protected SourceDefinitionsMutableInterface $definitions;

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
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @throws ContainerIdentifierExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function __construct(
        iterable|SourceDefinitionsMutableInterface $definitions = [],
        protected DiContainerConfigInterface       $config = new DiContainerNullConfig(),
        protected ?RemovedDefinitionIdsInterface   $removedDefinitionIds = null,
    ) {
        $this->definitions = !($definitions instanceof SourceDefinitionsMutableInterface)
            ? new ImmediateSourceDefinitionsMutable($definitions)
            : $definitions;
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|string $id
     *
     * @return mixed|T
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     *
     * @phpstan-ignore method.childReturnType, method.templateTypeNotInParameter
     */
    public function get(string $id): mixed
    {
        return array_key_exists($id, $this->resolved)
            ? $this->resolved[$id]
            : $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id])
            || array_key_exists($id, $this->resolved)
            || $this->hasViaZeroConfigurationDefinition($id)
            || $this->isContainer($id);
    }

    public function set(string $id, mixed $definition): static
    {
        $this->definitions[$id] = $definition;

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
     * @return iterable<class-string|non-empty-string, DiDefinitionType>
     */
    public function getDefinitions(): iterable
    {
        yield from $this->definitions->getIterator();
    }

    public function getConfig(): DiContainerConfigInterface
    {
        return $this->config;
    }

    public function findTaggedDefinitions(string $tag): iterable
    {
        $tagIsInterface = null;

        foreach ($this->definitions->getIterator() as $containerIdentifier => $definition) {
            if ($definition instanceof DiTaggedDefinitionInterface) {
                if ($definition instanceof DiDefinitionAutowireInterface) {
                    $tagIsInterface ??= interface_exists($tag);
                    // Pass container with configuration for determinate using php attribute or not.
                    $definition->setContainer($this);
                    $hasTag = true === $tagIsInterface
                        ? $definition->getDefinition()->implementsInterface($tag)
                        : $definition->hasTag($tag);
                } else {
                    $hasTag = $definition->hasTag($tag);
                }

                if ($hasTag) {
                    yield $containerIdentifier => $definition;
                }
            }
        }
    }

    /**
     * @return DiDefinitionInterface&DiDefinitionType
     */
    public function getDefinition(string $id): DiDefinitionInterface
    {
        if ($this->has($id)) {
            try {
                return $this->resolveDefinition($id);
            } catch (AutowireExceptionInterface $e) {
                throw new ContainerException(
                    sprintf('Cannot create definition via container identifier "%s".', $id),
                    previous: $e
                );
            }
        }

        throw new NotFoundException(id: $id);
    }

    /**
     * Resolve dependencies.
     *
     * @param class-string|string $id
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    protected function resolve(string $id, ?string $previousId = null): mixed
    {
        try {
            if ($this->isContainer($id)) {
                return $this;
            }

            if (!$this->has($id)) {
                $message = null !== $previousId
                    ? sprintf('Cannot resolve definition "%s" via container identifier "%s".', $id, $previousId)
                    : '';

                throw new NotFoundException(message: $message, id: $id);
            }

            $this->checkCyclicalDependencyCall($id);
            $this->circularCallWatcher[$id] = true;

            $diDefinition = $this->resolveDefinition($id);

            if ($diDefinition instanceof DiDefinitionLinkInterface) {
                $this->resolve($diDefinition->getDefinition(), $id);
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
     * @throws AutowireExceptionInterface
     */
    protected function resolveDefinition(string $id, ?string $previousId = null): DiDefinitionAutowireInterface|DiDefinitionInterface|DiDefinitionLinkInterface|DiDefinitionSingletonInterface|DiDefinitionTaggedAsInterface
    {
        if (isset($this->diResolvedDefinition[$id])) {
            return $this->diResolvedDefinition[$id];
        }

        if (isset($this->definitions[$id])) {
            return $this->definitions[$id]; // @phpstan-ignore return.type
        }

        try {
            $reflectionClass = new ReflectionClass($id); // @phpstan-ignore argument.type
        } catch (ReflectionException $e) {
            throw new AutowireException(
                sprintf('Cannot resolve definition "%s" via container identifier "%s".', $id, $previousId),
                previous: $e,
            );
        }

        if ($reflectionClass->isInterface()) {
            if ($this->config->isUseAttribute()
                && null !== ($service = AttributeReader::getServiceAttribute($reflectionClass))) {
                $findId = $service->id;

                try {
                    do {
                        $this->circularCallWatcher[$findId] = true;
                        $def = $this->resolveDefinition($findId, $reflectionClass->name);

                        if ($def instanceof DiDefinitionLinkInterface) {
                            $findId = $def->getDefinition();
                            $this->checkCyclicalDependencyCall($findId);
                        }
                    } while ($def instanceof DiDefinitionLinkInterface);
                } finally {
                    unset($this->circularCallWatcher[$findId]);
                }

                return $this->diResolvedDefinition[$id] = $this->getDiDefinitionWrapper($def, $service->isSingleton);
            }

            throw new AutowireException(sprintf('Attempting to resolve interface "%s". An interface that is not bound to a definition.', $id));
        }

        if ($this->config->isUseAttribute()
            && null !== ($factory = AttributeReader::getDiFactoryAttributeOnClass($reflectionClass))) {
            $diFactory = new DiDefinitionFactory($factory->definition, $factory->isSingleton);

            return $this->diResolvedDefinition[$id] = $diFactory->bindArguments(...$factory->arguments);
        }

        if ($this->config->isUseAttribute()
            && ($autowires = AttributeReader::getAutowireAttribute($reflectionClass))->valid()) {
            foreach ($autowires as $autowire) {
                if ($autowire->id === $reflectionClass->name) {
                    return $this->diResolvedDefinition[$id] = (new DiDefinitionAutowire($reflectionClass, $autowire->isSingleton))
                        ->bindArguments(...$autowire->arguments)
                    ;
                }
            }
        }

        return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire($reflectionClass, $this->config->isSingletonServiceDefault());
    }

    protected function isContainer(string $id): bool
    {
        return in_array($id, [ContainerInterface::class, DiContainerInterface::class, __CLASS__], true);
    }

    protected function hasViaZeroConfigurationDefinition(string $id): bool
    {
        if (!$this->config->isUseZeroConfigurationDefinition()) {
            return false;
        }

        if (isset($this->removedDefinitionIds?->getRemovedDefinitionIds()[$id])) {
            return false;
        }

        if (class_exists($id) || interface_exists($id)) {
            return !$this->config->isUseAttribute() || !AttributeReader::isAutowireExclude(new ReflectionClass($id));
        }

        return false;
    }

    protected function checkCyclicalDependencyCall(string $id): void
    {
        if (array_key_exists($id, $this->circularCallWatcher)) {
            throw new CallCircularDependencyException(callIds: [...array_keys($this->circularCallWatcher), $id]);
        }
    }

    private function getDiDefinitionWrapper(DiDefinitionAutowireInterface|DiDefinitionInterface $def, ?bool $singleton): DiDefinitionSingletonInterface
    {
        return new class($def, $singleton) implements DiDefinitionSingletonInterface {
            public function __construct(
                private readonly DiDefinitionAutowireInterface|DiDefinitionInterface $def,
                private readonly ?bool $isSingleton
            ) {}

            public function getDefinition(): DiDefinitionAutowireInterface|DiDefinitionInterface
            {
                return $this->def; // @codeCoverageIgnore
            }

            public function resolve(DiContainerInterface $container, mixed $context = null): mixed
            {
                return $this->def->resolve($container, $context);
            }

            public function isSingleton(): ?bool
            {
                return $this->isSingleton;
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Closure;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
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
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionMethod;

use function array_key_exists;
use function array_keys;
use function call_user_func_array;
use function class_exists;
use function implode;
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
 *
 * @phpstan-type DiDefinitionResolvable DiDefinitionAutowireInterface|DiDefinitionInterface|DiDefinitionLinkInterface|DiDefinitionSingletonInterface|DiDefinitionTaggedAsInterface
 */
class DiContainer implements DiContainerInterface, DiContainerSetterInterface, DiContainerCallInterface
{
    /**
     * Default singleton for definitions.
     */
    protected bool $isSingletonDefault;

    /**
     * @var array<class-string|non-empty-string, mixed>
     */
    protected array $definitions = [];

    /**
     * @var array<class-string|string, DiDefinitionResolvable>
     */
    protected array $diResolvedDefinition = [];

    /**
     * @var array<class-string|string, mixed>
     */
    protected array $resolved = [];

    /**
     * @var array<class-string|string, bool>
     */
    protected array $resolvingDependencies = [];

    /**
     * @param iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed> $definitions
     *
     * @throws ContainerIdentifierExceptionInterface
     * @throws ContainerAlreadyRegisteredExceptionInterface
     */
    public function __construct(
        iterable $definitions = [],
        protected ?DiContainerConfigInterface $config = null
    ) {
        $this->isSingletonDefault = $this->config?->isSingletonServiceDefault() ?? false;

        foreach ($definitions as $identifier => $definition) {
            $this->set(Helper::getContainerIdentifier($identifier, $definition), $definition);
        }
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
        return array_key_exists($id, $this->definitions)
            || array_key_exists($id, $this->resolved)
            || $this->hasViaZeroConfigurationDefinition($id)
            || $this->isContainer($id);
    }

    public function set(string $id, mixed $definition): static
    {
        Helper::getContainerIdentifier($id, $definition);

        if (array_key_exists($id, $this->definitions)) {
            throw new ContainerAlreadyRegisteredException(
                sprintf('Definition identifier "%s" already registered in container.', $id)
            );
        }

        $this->definitions[$id] = $definition;

        return $this;
    }

    public function call(array|callable|string $definition, array $arguments = []): mixed
    {
        $reflectionDefinition = DefinitionDiCall::getReflection($definition);

        if ($reflectionDefinition instanceof ReflectionMethod) {
            if ($reflectionDefinition->isStatic()) {
                return call_user_func_array(
                    [$reflectionDefinition->class, $reflectionDefinition->name], // @phpstan-ignore argument.type
                    ArgumentResolver::resolve(new ArgumentBuilder($arguments, $reflectionDefinition, $this), $this)
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
                ArgumentResolver::resolve(new ArgumentBuilder($arguments, $reflectionDefinition, $this), $this)
            );
        }

        return call_user_func_array(
            $definition, // @phpstan-ignore argument.type
            ArgumentResolver::resolve(new ArgumentBuilder($arguments, $reflectionDefinition, $this), $this)
        );
    }

    /**
     * @return iterable<class-string|non-empty-string, DiDefinitionType>
     */
    public function getDefinitions(): iterable
    {
        foreach ($this->definitions as $id => $definition) {
            yield $id => $this->clarificationOfDefinition($definition);
        }
    }

    public function getConfig(): ?DiContainerConfigInterface
    {
        return $this->config;
    }

    public function findTaggedDefinitions(string $tag): iterable
    {
        $tagIsInterface = null;

        foreach ($this->definitions as $containerIdentifier => $definition) {
            if ($definition instanceof DiTaggedDefinitionInterface) {
                if ($definition instanceof DiDefinitionAutowireInterface) {
                    $tagIsInterface ??= interface_exists($tag);
                    // Pass container with configuration for determinate using php attribute or not.
                    $definition->setContainer($this);

                    if (true === $tagIsInterface) {
                        $hasTag = $definition->getDefinition()->implementsInterface($tag);
                    } else {
                        $hasTag = $definition->hasTag($tag);
                    }
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
     * Resolve dependencies.
     *
     * @param class-string|string $id
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    protected function resolve(string $id): mixed
    {
        try {
            if ($this->isContainer($id)) {
                return $this;
            }

            if (!$this->has($id)) {
                throw new NotFoundException(sprintf('Unresolvable dependency "%s".', $id));
            }

            $this->checkCyclicalDependencyCall($id);
            $this->resolvingDependencies[$id] = true;

            $diDefinition = $this->resolveDefinition($id);
            $resolvedEntry = $diDefinition->resolve($this, $this);

            if ($diDefinition instanceof DiDefinitionSingletonInterface
                && !($diDefinition->isSingleton() ?? $this->isSingletonDefault)) {
                return $resolvedEntry;
            }

            return $this->resolved[$id] = $resolvedEntry;
        } finally {
            unset($this->resolvingDependencies[$id]);
        }
    }

    /**
     * @param class-string|string $id
     *
     * @throws AutowireExceptionInterface
     */
    protected function resolveDefinition(string $id): DiDefinitionAutowireInterface|DiDefinitionInterface|DiDefinitionLinkInterface|DiDefinitionSingletonInterface|DiDefinitionTaggedAsInterface
    {
        if (isset($this->diResolvedDefinition[$id])) {
            return $this->diResolvedDefinition[$id];
        }

        $hasDefinition = array_key_exists($id, $this->definitions);

        if ($hasDefinition
            && !$this->definitions[$id] instanceof DiDefinitionLinkInterface
            && $this->definitions[$id] instanceof DiDefinitionInterface) {
            return $this->definitions[$id];
        }

        if (!$hasDefinition) {
            // @phpstan-ignore argument.type
            $reflectionClass = new ReflectionClass($id); // @todo come up with a test for throw ReflectionException

            if ($reflectionClass->isInterface()) {
                // @phpstan-ignore-next-line booleanAnd.leftNotBoolean
                if ($this->config?->isUseAttribute()
                    && $service = AttributeReader::getServiceAttribute($reflectionClass)) {
                    $this->checkCyclicalDependencyCall($service->getIdentifier());
                    $this->resolvingDependencies[$service->getIdentifier()] = true;

                    try {
                        $def = $this->resolveDefinition($service->getIdentifier());

                        if (!$def instanceof DiDefinitionLinkInterface) {
                            return $this->diResolvedDefinition[$id] = $this->getDiDefinitionWrapper($def, $service->isSingleton());
                        }
                    } finally {
                        unset($this->resolvingDependencies[$service->getIdentifier()]);
                    }
                }

                throw new NotFoundException(sprintf('Definition not found for interface "%s".', $id));
            }

            // @phpstan-ignore-next-line booleanAnd.leftNotBoolean
            if ($this->config?->isUseAttribute()
                && $factory = AttributeReader::getDiFactoryAttribute($reflectionClass)) {
                return $this->diResolvedDefinition[$id] = new DiDefinitionFactory(
                    $factory->getIdentifier(),
                    $factory->isSingleton()
                );
            }

            // @phpstan-ignore-next-line booleanAnd.leftNotBoolean
            if ($this->config?->isUseAttribute()
                && ($autowires = AttributeReader::getAutowireAttribute($reflectionClass))->valid()) {
                foreach ($autowires as $autowire) {
                    if ($autowire->getIdentifier() === $reflectionClass->name) {
                        return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire($reflectionClass, $autowire->isSingleton());
                    }
                }
            }

            return $this->diResolvedDefinition[$id] = new DiDefinitionAutowire($reflectionClass, $this->isSingletonDefault);
        }

        $definition = $this->clarificationOfDefinition($this->definitions[$id]);

        if ($definition instanceof DiDefinitionLinkInterface) {
            $this->checkCyclicalDependencyCall($definition->getDefinition());
            $this->resolvingDependencies[$definition->getDefinition()] = true;

            try {
                return $this->diResolvedDefinition[$id] = $this->resolveDefinition($definition->getDefinition());
            } finally {
                unset($this->resolvingDependencies[$definition->getDefinition()]);
            }
        }

        return $this->diResolvedDefinition[$id] = $definition;
    }

    protected function clarificationOfDefinition(mixed $definition): DiDefinitionCallable|DiDefinitionInterface|DiDefinitionValue
    {
        return match (true) {
            $definition instanceof DiDefinitionInterface => $definition,
            $definition instanceof Closure => new DiDefinitionCallable($definition, $this->isSingletonDefault),
            default => new DiDefinitionValue($definition)
        };
    }

    protected function isContainer(string $id): bool
    {
        return in_array($id, [ContainerInterface::class, DiContainerInterface::class, __CLASS__], true);
    }

    protected function hasViaZeroConfigurationDefinition(string $id): bool
    {
        if (!$this->config?->isUseZeroConfigurationDefinition()) { // @phpstan-ignore booleanNot.exprNotBoolean
            return false;
        }

        if (class_exists($id) || interface_exists($id)) {
            return !$this->config->isUseAttribute() || !AttributeReader::isAutowireExclude(new ReflectionClass($id));
        }

        return false;
    }

    protected function checkCyclicalDependencyCall(string $id): void
    {
        if (array_key_exists($id, $this->resolvingDependencies)) {
            $callPath = implode(' -> ', array_keys($this->resolvingDependencies)).' -> '.$id;

            throw new CallCircularDependencyException(
                sprintf('Trying call cyclical dependency. Call dependencies: %s.', $callPath)
            );
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

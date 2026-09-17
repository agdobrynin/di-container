<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Closure;
use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DTO\SetupArgumentBuilder;
use Kaspi\DiContainer\DTO\SetupTypeWithArguments;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\SetupArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterSetterInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedObjectDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\FreezeInterface;
use Kaspi\DiContainer\Interfaces\ResetInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\TagsOnObjectDefinitionTrait;
use ReflectionClass;
use ReflectionException;

use function call_user_func_array;
use function get_class;
use function get_debug_type;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

use const PHP_VERSION_ID;

final class DiDefinitionAutowire implements DiDefinitionAutowireInterface, DiDefinitionSetupAutowireInterface, DiDefinitionIdentifierInterface, DiDefinitionTagArgumentInterface, DiTaggedObjectDefinitionInterface, ResetInterface, FreezeInterface, DiDefinitionResetterSetterInterface, DiDefinitionResetterInterface
{
    use BindArgumentsTrait {
        bindArguments as private bindArgumentsInternal;
    }

    use TagsOnObjectDefinitionTrait {
        reset as private resetTrait;
    }

    private ReflectionClass $reflectionClass;

    private ArgumentBuilderInterface|false $constructArgBuilder;

    /**
     * @var list<SetupArgumentBuilderInterface>
     */
    private array $setupArgBuilders;

    /**
     * Methods for setup service by PHP definition via setters (mutable or immutable).
     *
     * @var array<non-empty-string, list<SetupTypeWithArguments>>
     */
    private array $setup = [];

    /**
     * Methods for setup service by PHP attribute via setters (mutable or immutable).
     *
     * @var array<non-empty-string, list<SetupTypeWithArguments>>
     */
    private array $setupByAttributes;

    /**
     * @var null|non-empty-string
     */
    private ?string $containerIdentifier = null;

    /**
     * @var callable(object): void|false|non-empty-string
     */
    private $resetter = false;

    private mixed $context = null;

    /**
     * @param class-string|ReflectionClass $definition
     */
    public function __construct(
        private readonly ReflectionClass|string $definition,
        private readonly ?bool $isSingleton = null,
        private readonly bool $isLazy = false,
    ) {
        if ($this->definition instanceof ReflectionClass) {
            $this->reflectionClass = $this->definition;
        }
    }

    public function setup(string $method, array $arguments = []): static
    {
        if ($this->isFrozen) {
            throw new DiDefinitionException(
                sprintf('Cannot call \%s::setup() on a frozen definition.', __CLASS__)
            );
        }

        $this->setup[$method][] = new SetupTypeWithArguments(SetupConfigureMethod::Mutable, $arguments);
        unset($this->setupArgBuilders);

        return $this;
    }

    public function setupImmutable(string $method, array $arguments = []): static
    {
        if ($this->isFrozen) {
            throw new DiDefinitionException(
                sprintf('Cannot call \%s::setupImmutable() on a frozen definition.', __CLASS__)
            );
        }

        $this->setup[$method][] = new SetupTypeWithArguments(SetupConfigureMethod::Immutable, $arguments);
        unset($this->setupArgBuilders);

        return $this;
    }

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArgumentsInternal(...$argument);
        unset($this->constructArgBuilder);

        return $this;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function exposeArgumentBuilder(DiContainerInterface $container): ?ArgumentBuilderInterface
    {
        $this->checkIsInstantiable();
        $constructor = $this->getDefinition()->getConstructor();

        if (null === $constructor) {
            return null;
        }

        if ($this->context instanceof Autowire) {
            return new ArgumentBuilder($this->context->arguments + $this->bindArguments, $constructor, $container, true);
        }

        return new ArgumentBuilder($this->bindArguments, $constructor, $container, false);
    }

    public function exposeSetupArgumentBuilders(DiContainerInterface $container): array
    {
        $this->checkIsInstantiable();

        /** @var list<SetupArgumentBuilderInterface> $setupArgBuilders */
        $setupArgBuilders = [];

        foreach ($this->getSetups($this->getDefinition(), $container) as $method => $callsSetupTypeWithArguments) {
            try {
                $reflectionMethod = $this->getDefinition()->getMethod($method);
            } catch (ReflectionException $e) {
                throw new DiDefinitionException(
                    message: sprintf('The setter method "%s::%s()" does not exist.', $this->getDefinition()->getName(), $method),
                    previous: $e
                );
            }

            if ($reflectionMethod->isConstructor() || $reflectionMethod->isDestructor()) {
                throw new DiDefinitionException(sprintf('Cannot use "%s" as setter.', Helper::functionName($reflectionMethod)));
            }

            foreach ($callsSetupTypeWithArguments as $setupTypeWithArguments) {
                $setupArgBuilders[] = new SetupArgumentBuilder(new ArgumentBuilder($setupTypeWithArguments->arguments, $reflectionMethod, $container, true), $setupTypeWithArguments->setupType);
            }
        }

        return $setupArgBuilders;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): object
    {
        if (false === $this->isLazy) {
            return $this->objectInitializer($container);
        }

        if (PHP_VERSION_ID < 80400) {
            throw new DiDefinitionException(
                sprintf('Lazy object require PHP 8.4 or higher. Current PHP version: %s.', PHP_VERSION_ID)
            );
        }

        /** @var Closure(): object $initializerProxy */
        $initializerProxy = fn (): object => $this->objectInitializer($container);

        // @phpstan-ignore-next-line-until 8.4
        return $this->getDefinition()->newLazyProxy($initializerProxy);
    }

    public function getDefinition(): ReflectionClass
    {
        try {
            return $this->reflectionClass ??= new ReflectionClass($this->definition);
        } catch (ReflectionException $e) { // @phpstan-ignore catch.neverThrown
            throw new DiDefinitionException($e->getMessage());
        }
    }

    /**
     * @return class-string
     */
    public function getIdentifier(): string
    {
        return is_string($this->definition)
            ? $this->definition
            : $this->reflectionClass->getName();
    }

    public function isImplementInterface(string $interface): bool
    {
        return $this->getDefinition()->implementsInterface($interface);
    }

    public function getInterfaceNames(): array
    {
        return $this->getDefinition()->getInterfaceNames();
    }

    public function getDefinitionIdentifier(): string
    {
        return $this->getIdentifier();
    }

    public function reset(): void
    {
        $this->resetTrait();

        unset(
            $this->constructArgBuilder,
            $this->setupArgBuilders,
            $this->setupByAttributes,
        );

        if (is_string($this->definition)) {
            unset($this->reflectionClass);
        }

        $this->containerIdentifier = null;
    }

    public function setContainerIdentifier(string $containerIdentifier): void
    {
        if ($this->isFrozen) {
            throw new DiDefinitionException(
                sprintf('Cannot call \%s::setContainerIdentifier() on a frozen definition.', __CLASS__)
            );
        }

        if ($containerIdentifier !== $this->containerIdentifier) {
            unset(
                $this->tagsByAttribute,
                $this->setupByAttributes,
            );
        }

        $this->containerIdentifier = $containerIdentifier;
    }

    public function getContainerIdentifier(): ?string
    {
        return $this->containerIdentifier;
    }

    public function setResetter(callable|false|string $resetter): static
    {
        if ($this->isFrozen) {
            throw new DiDefinitionException(
                sprintf('Cannot call \%s::setResetter() on a frozen definition.', __CLASS__)
            );
        }

        $this->resetter = $resetter;

        return $this;
    }

    public function getResetter(): callable|false|string
    {
        try {
            if (false === $this->resetter && $this->isImplementInterface(ResetInterface::class)) {
                return 'reset';
            }
        } catch (DiDefinitionExceptionInterface) {
            return false;
        }

        return $this->resetter;
    }

    public function isLazy(): bool
    {
        return $this->isLazy;
    }

    public function setContext(mixed $context): static
    {
        if ($this->isFrozen) {
            throw new DiDefinitionException(
                sprintf('Cannot call \%s::setContext() on a frozen definition.', __CLASS__)
            );
        }

        $this->context = $context;

        return $this;
    }

    public function getContext(): mixed
    {
        return $this->context;
    }

    protected function readTagAttributes(): Generator
    {
        try {
            $reflectionClass = $this->getDefinition();
            $autowireAttribute = $this->getAutowireAttributeConfiguringDefinition($reflectionClass);
        } catch (AutowireAttributeException|DiDefinitionException $e) {
            throw new DiDefinitionException(
                sprintf('Cannot read php attribute "%s" on class "%s".', Tag::class, $this->getDefinitionIdentifier()),
                previous: $e
            );
        }

        if (false === $autowireAttribute || null === $autowireAttribute->tags) {
            yield from AttributeReader::getTagAttribute($reflectionClass);

            return;
        }

        if ($autowireAttribute->tags instanceof Tag) {
            yield $autowireAttribute->tags;

            return;
        }

        foreach ($autowireAttribute->tags as $argTag) {
            if ($argTag instanceof Tag) {
                yield $argTag;
            }
        }
    }

    private function objectInitializer(DiContainerInterface $container): object
    {
        $this->constructArgBuilder ??= ($this->exposeArgumentBuilder($container) ?? false);

        /** @var object $object */
        $object = (false === $this->constructArgBuilder)
            ? $this->getDefinition()->newInstanceWithoutConstructor()
            : $this->getDefinition()->newInstanceArgs($this->constructArgBuilder->resolve($this));

        if (!isset($this->setupArgBuilders)) {
            $this->setupArgBuilders = $this->exposeSetupArgumentBuilders($container);
        }

        foreach ($this->setupArgBuilders as $argBuilder) {
            $resolvedArguments = $argBuilder->argumentBuilder()->resolve($this);
            $reflectionMethod = $argBuilder->argumentBuilder()->getFunctionOrMethod();

            /** @var callable $callable */
            $callable = [$object, $reflectionMethod->name];

            if (SetupConfigureMethod::Mutable === $argBuilder->setupType()) {
                call_user_func_array($callable, $resolvedArguments);

                continue;
            }

            $result = call_user_func_array($callable, $resolvedArguments);

            if (is_object($result) && get_class($result) === get_class($object)) {
                /** @var object $object */
                $object = $result;
                unset($result);

                continue;
            }

            throw new DiDefinitionException(sprintf('The immutable setter "%s" must return same class "%s". Got type: %s', Helper::functionName($reflectionMethod), $this->getDefinition()->getName(), get_debug_type($result)));
        }

        return $object;
    }

    /**
     * @return array<non-empty-string, list<SetupTypeWithArguments>>
     *
     * @throws AutowireExceptionInterface
     */
    private function getSetups(ReflectionClass $class, DiContainerInterface $container): array
    {
        if (!$container->getConfig()->isUseAttribute()) {
            return $this->setup;
        }

        if (!isset($this->setupByAttributes)) {
            $this->setupByAttributes = [];

            foreach ($this->getSetupAttributes($class) as $setupAttr) {
                $setupType = $setupAttr instanceof Setup
                    ? SetupConfigureMethod::Mutable
                    : SetupConfigureMethod::Immutable;

                $this->setupByAttributes[$setupAttr->getMethod()][] = new SetupTypeWithArguments($setupType, $setupAttr->arguments);
            }
        }

        return $this->setupByAttributes + $this->setup;
    }

    /**
     * @return Generator<Setup|SetupImmutable>
     *
     * @throws AutowireAttributeException
     */
    private function getSetupAttributes(ReflectionClass $class): Generator
    {
        $autowireAttribute = $this->getContext() instanceof Autowire
            ? $this->getContext()
            : $this->getAutowireAttributeConfiguringDefinition($class);

        if (false === $autowireAttribute || null === $autowireAttribute->setups) {
            yield from AttributeReader::getSetupAttribute($class);

            return;
        }

        foreach ($autowireAttribute->setups as $method => $setups) {
            if (is_array($setups)) {
                foreach ($setups as $setup) {
                    if ($setup instanceof Setup || $setup instanceof SetupImmutable) {
                        $setup->setMethod($method);

                        yield $setup;
                    }
                }
            } elseif ($setups instanceof Setup || $setups instanceof SetupImmutable) {
                $setups->setMethod($method);

                yield $setups;
            }
        }
    }

    /**
     * @throws AutowireAttributeException
     */
    private function getAutowireAttributeConfiguringDefinition(ReflectionClass $class): Autowire|false
    {
        // We need to ensure that all attributes that have `Autowire::$id` are unique.
        /** @var list<Autowire> $attrs */
        $attrs = [...AttributeReader::getAutowireAttribute($class)];

        if (null === $this->getContainerIdentifier()) {
            foreach ($attrs as $attribute) {
                if ('' === $attribute->id) {
                    return $attribute;
                }
            }

            return false;
        }

        foreach ($attrs as $attribute) {
            if ($this->getContainerIdentifier() === $attribute->id) {
                return $attribute;
            }

            if ('' === $attribute->id && $this->getContainerIdentifier() === $class->getName()) {
                return $attribute;
            }
        }

        return false;
    }

    /**
     * @throws DiDefinitionExceptionInterface
     */
    private function checkIsInstantiable(): void
    {
        if (!$this->getDefinition()->isInstantiable()) {
            throw new DiDefinitionException(sprintf('The "%s" class is not instantiable.', $this->getDefinition()->getName()));
        }
    }
}

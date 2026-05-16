<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedObjectDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\TagsOnObjectDefinitionTrait;
use ReflectionClass;
use ReflectionException;

use function call_user_func_array;
use function get_class;
use function get_debug_type;
use function is_object;
use function is_string;
use function sprintf;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 *
 * @phpstan-type SetupConfigureArgumentsType array<non-empty-string|non-negative-int, DiDefinitionType|mixed>
 * @phpstan-type SetupConfigureItem array{0: SetupConfigureMethod, 1: SetupConfigureArgumentsType}
 */
final class DiDefinitionAutowire implements DiDefinitionAutowireInterface, DiDefinitionSetupAutowireInterface, DiDefinitionIdentifierInterface, DiDefinitionTagArgumentInterface, DiTaggedObjectDefinitionInterface
{
    use BindArgumentsTrait {
        bindArguments as private bindArgumentsInternal;
    }

    use TagsOnObjectDefinitionTrait;

    private ReflectionClass $reflectionClass;

    private ArgumentBuilderInterface|false $constructArgBuilder;

    /**
     * @var list<array{0: SetupConfigureMethod, 1: ArgumentBuilderInterface}>
     */
    private array $setupArgBuilders;

    /**
     * Methods for setup service by PHP definition via setters (mutable or immutable).
     *
     * @var array<non-empty-string, list<SetupConfigureItem>>
     */
    private array $setup = [];

    /**
     * Methods for setup service by PHP attribute via setters (mutable or immutable).
     *
     * @var array<non-empty-string, list<SetupConfigureItem>>
     */
    private array $setupByAttributes;

    /**
     * @param class-string|ReflectionClass $definition
     */
    public function __construct(private readonly ReflectionClass|string $definition, private readonly ?bool $isSingleton = null)
    {
        if ($this->definition instanceof ReflectionClass) {
            $this->reflectionClass = $this->definition;
        }
    }

    /**
     * @return $this
     */
    public function setup(string $method, array $arguments = []): static
    {
        unset($this->setupArgBuilders);
        $this->setup[$method][] = [SetupConfigureMethod::Mutable, $arguments];

        return $this;
    }

    public function setupImmutable(string $method, array $arguments = []): static
    {
        unset($this->setupArgBuilders);
        $this->setup[$method][] = [SetupConfigureMethod::Immutable, $arguments];

        return $this;
    }

    public function bindArguments(mixed ...$argument): static
    {
        unset($this->constructArgBuilder);
        $this->bindArgumentsInternal(...$argument);

        return $this;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function exposeArgumentBuilder(DiContainerInterface $container): ?ArgumentBuilderInterface
    {
        $this->checkIsInstantiable();

        return (null !== ($constructor = $this->getDefinition()->getConstructor()))
            ? new ArgumentBuilder($this->bindArguments, $constructor, $container)
            : null;
    }

    public function exposeSetupArgumentBuilders(DiContainerInterface $container): array
    {
        $this->checkIsInstantiable();
        $setupArgBuilders = [];

        foreach ($this->getSetups($this->getDefinition(), $container) as $method => $calls) {
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

            foreach ($calls as [$setupConfigureType, $callArguments]) {
                $setupArgBuilders[] = [$setupConfigureType, new ArgumentBuilder($callArguments, $reflectionMethod, $container)];
            }
        }

        return $setupArgBuilders;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): object
    {
        $this->constructArgBuilder ??= ($this->exposeArgumentBuilder($container) ?? false);

        /** @var object $object */
        $object = (false === $this->constructArgBuilder)
            ? $this->getDefinition()->newInstanceWithoutConstructor()
            : $this->getDefinition()->newInstanceArgs(ArgumentResolver::resolve($this->constructArgBuilder, $container, $this));

        $this->setupArgBuilders ??= $this->exposeSetupArgumentBuilders($container);

        /** @var ArgumentBuilderInterface $argBuilder */
        foreach ($this->setupArgBuilders as [$setupConfigureType, $argBuilder]) {
            $resolvedArguments = ArgumentResolver::resolveByPriorityBindArguments($argBuilder, $container, $this);
            $reflectionMethod = $argBuilder->getFunctionOrMethod();

            /** @var callable $callable */
            $callable = [$object, $reflectionMethod->name];

            if (SetupConfigureMethod::Mutable === $setupConfigureType) {
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

    public function getDefinitionIdentifier(): string
    {
        return $this->getIdentifier();
    }

    /**
     * @return array<non-empty-string, list<SetupConfigureItem>>
     */
    private function getSetups(ReflectionClass $class, DiContainerInterface $container): array
    {
        if (!$container->getConfig()->isUseAttribute()) {
            return $this->setup;
        }

        if (!isset($this->setupByAttributes)) {
            $this->setupByAttributes = [];

            foreach (AttributeReader::getSetupAttribute($class) as $setupAttr) {
                $setupType = $setupAttr instanceof Setup
                    ? SetupConfigureMethod::Mutable
                    : SetupConfigureMethod::Immutable;

                $this->setupByAttributes[$setupAttr->getMethod()][] = [$setupType, $setupAttr->arguments];
            }
        }

        return $this->setupByAttributes + $this->setup;
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

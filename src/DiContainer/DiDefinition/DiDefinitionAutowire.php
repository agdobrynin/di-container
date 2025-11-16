<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiAutowireTrait;
use Kaspi\DiContainer\Traits\SetupConfigureTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;

use function get_class;
use function get_debug_type;
use function is_object;
use function is_string;
use function sprintf;

/**
 * @phpstan-import-type Tags from DiTaggedDefinitionInterface
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 * @phpstan-import-type SetupConfigureItem from SetupConfigureTrait
 */
final class DiDefinitionAutowire implements DiDefinitionSetupAutowireInterface, DiDefinitionSingletonInterface, DiDefinitionIdentifierInterface, DiDefinitionAutowireInterface, DiDefinitionTagArgumentInterface
{
    use AttributeReaderTrait;
    use BindArgumentsTrait {
        bindArguments as private bindArgumentsInternal;
    }
    use DiAutowireTrait;
    use TagsTrait {
        getTags as private getTagsInternal;
        hasTag as private hasTagInternal;
        geTagPriority as private geTagPriorityInternal;
    }
    use SetupConfigureTrait {
        setup as private setupInternal;
        setupImmutable as private setupImmutableInternal;
    }

    private ReflectionClass $reflectionClass;

    private ArgumentBuilder|false $constructArgBuilder;

    /**
     * @var array<non-empty-string, array<non-negative-int, ArgumentBuilder>>
     */
    private array $setupArgBuilder = [];

    /**
     * Tags from php attributes on class.
     *
     * @var array<non-empty-string, TagOptions>
     */
    private array $tagsByAttribute;

    private DiContainerInterface $container;

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
    public function setup(string $method, mixed ...$argument): static
    {
        unset($this->setupArgBuilder[$method]);
        $this->setupInternal($method, ...$argument);

        return $this;
    }

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArgumentsInternal(...$argument);
        unset($this->constructArgBuilder);

        return $this;
    }

    public function setupImmutable(string $method, mixed ...$argument): static
    {
        $this->setupImmutableInternal($method, ...$argument);
        unset($this->setupArgBuilder[$method]);

        return $this;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function setContainer(DiContainerInterface $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): object
    {
        $this->setContainer($container);

        /** @var object $object */
        $object = $this->newInstance();

        foreach ($this->getSetups($this->getDefinition(), $container) as $method => $calls) {
            if (!$this->getDefinition()->hasMethod($method)) {
                throw new AutowireException(sprintf('The setter method "%s::%s()" does not exist.', $this->getDefinition()->getName(), $method));
            }

            $reflectionMethod = $this->getDefinition()->getMethod($method);

            if ($reflectionMethod->isConstructor() || $reflectionMethod->isDestructor()) {
                throw new AutowireException(sprintf('Cannot use %s::%s() as setter.', $this->getDefinition()->name, $method));
            }

            foreach ($calls as $index => [$setupConfigureType, $callArguments]) {
                $argBuilder = $this->setupArgBuilder[$method][$index] ??= new ArgumentBuilder($callArguments, $reflectionMethod, $this->getContainer());

                $resolvedArguments = [];

                foreach ($argBuilder->buildByPriorityBindArguments() as $argNameOrIndex => $arg) {
                    $resolvedArguments[$argNameOrIndex] = $arg instanceof DiDefinitionInterface
                        ? $arg->resolve($container, $this)
                        : $arg;
                }

                if (SetupConfigureMethod::Mutable === $setupConfigureType) {
                    $reflectionMethod->invokeArgs($object, $resolvedArguments);

                    continue;
                }

                $result = $reflectionMethod->invokeArgs($object, $resolvedArguments);

                if (is_object($result) && get_class($result) === get_class($object)) {
                    /** @var object $object */
                    $object = $result;
                    unset($result);

                    continue;
                }

                throw new AutowireException(
                    sprintf(
                        'The immutable setter "%s::%s()" must return same class "%s". Got type: %s',
                        $this->getDefinition()->getName(),
                        $method,
                        $this->getDefinition()->getName(),
                        get_debug_type($result)
                    )
                );
            }
        }

        return $object;
    }

    public function getDefinition(): ReflectionClass // @phpstan-ignore throws.unusedType
    {
        try {
            return $this->reflectionClass ??= new ReflectionClass($this->definition);
        } catch (ReflectionException $e) { // @phpstan-ignore catch.neverThrown
            throw new AutowireException(message: $e->getMessage());
        }
    }

    /**
     * @return class-string|non-empty-string
     */
    public function getIdentifier(): string
    {
        return is_string($this->definition)
            ? $this->definition
            : $this->reflectionClass->getName();
    }

    public function getTags(): array
    {
        // 🚩 PHP attributes have higher priority than PHP definitions (see documentation.)
        return $this->getTagsByAttribute() + $this->getTagsInternal();
    }

    public function hasTag(string $name): bool
    {
        return isset($this->getTags()[$name]);
    }

    public function getTag(string $name): ?array
    {
        return $this->getTags()[$name] ?? null;
    }

    /**
     * @param non-empty-string $name
     * @param TagOptions       $operationOptions
     */
    public function geTagPriority(string $name, array $operationOptions = []): int|string|null
    {
        if (null !== ($priority = $this->geTagPriorityInternal($name, $operationOptions))) {
            return $priority;
        }

        $tagOptions = $operationOptions + ($this->getTag($name) ?? []);

        if (isset($tagOptions['priority.method'])) {
            $howGetPriority = sprintf('Get priority by option "priority.method" for tag "%s".', $name);

            // @phpstan-ignore return.type
            return self::callStaticMethod($this, $tagOptions['priority.method'], true, $howGetPriority, ['int', 'string', 'null'], $name, $tagOptions);
        }

        $priorityDefaultMethod = ($tagOptions['priority.default_method'] ?? null);

        if (null !== $priorityDefaultMethod) {
            $howGetPriority = sprintf('Get priority by option "priority.default_method" for class "%s".', $this->getDefinition()->getName());

            // @phpstan-ignore return.type
            return self::callStaticMethod($this, $priorityDefaultMethod, false, $howGetPriority, ['int', 'string', 'null'], $name, $tagOptions);
        }

        return null;
    }

    private function getContainer(): DiContainerInterface
    {
        if (!isset($this->container)) {
            throw new DiDefinitionException(
                sprintf('Need set container implementation. Use method %s::setContainer(). Definition identifier "%s".', __CLASS__, $this->getIdentifier())
            );
        }

        return $this->container;
    }

    /**
     * @throws AutowireExceptionInterface|ContainerExceptionInterface|NotFoundExceptionInterface
     */
    private function newInstance(): object
    {
        if (!$this->getDefinition()->isInstantiable()) {
            throw new AutowireException(
                sprintf('The "%s" class is not instantiable.', $this->getDefinition()->getName())
            );
        }

        if (!isset($this->constructArgBuilder)) {
            $this->constructArgBuilder = null !== ($constructor = $this->getDefinition()->getConstructor())
                ? new ArgumentBuilder($this->getBindArguments(), $constructor, $this->getContainer())
                : false;
        }

        if (false === $this->constructArgBuilder) {
            return $this->getDefinition()->newInstanceWithoutConstructor();
        }

        $resolvedArguments = [];

        foreach ($this->constructArgBuilder->build() as $argNameOrIndex => $arg) {
            $resolvedArguments[$argNameOrIndex] = $arg instanceof DiDefinitionInterface
                ? $arg->resolve($this->getContainer(), $this)
                : $arg;
        }

        return $this->getDefinition()->newInstanceArgs($resolvedArguments);
    }

    /**
     * @return array<non-empty-string, TagOptions>
     *
     * @throws AutowireExceptionInterface
     */
    private function getTagsByAttribute(): array
    {
        if (false === (bool) $this->getContainer()->getConfig()?->isUseAttribute()) {
            return [];
        }

        if (isset($this->tagsByAttribute)) {
            return $this->tagsByAttribute;
        }

        $this->tagsByAttribute = [];

        foreach ($this->getTagAttribute($this->getDefinition()) as $tagAttribute) {
            $priorityMethod = null !== $tagAttribute->getPriorityMethod()
                ? ['priority.method' => $tagAttribute->getPriorityMethod()]
                : [];
            $this->tagsByAttribute[$tagAttribute->getIdentifier()] = self::transformTagOptions(
                $priorityMethod + $tagAttribute->getOptions(),
                $tagAttribute->getPriority()
            );
        }

        return $this->tagsByAttribute;
    }
}

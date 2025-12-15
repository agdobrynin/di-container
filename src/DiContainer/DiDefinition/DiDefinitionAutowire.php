<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use InvalidArgumentException;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupConfigureInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSingletonInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\SetupConfigureTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use ReflectionClass;
use ReflectionException;

use function call_user_func_array;
use function get_class;
use function get_debug_type;
use function is_callable;
use function is_int;
use function is_null;
use function is_object;
use function is_string;
use function sprintf;
use function trim;
use function var_export;

/**
 * @phpstan-import-type Tags from DiTaggedDefinitionInterface
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 * @phpstan-import-type SetupConfigureItem from SetupConfigureTrait
 */
final class DiDefinitionAutowire implements DiDefinitionAutowireInterface, DiDefinitionSetupAutowireInterface, DiDefinitionSingletonInterface, DiDefinitionIdentifierInterface, DiDefinitionTagArgumentInterface
{
    use BindArgumentsTrait {
        bindArguments as private bindArgumentsInternal;
    }
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

    private ArgumentBuilderInterface|false $constructArgBuilder;

    /**
     * @var list<array{0: DiDefinitionSetupConfigureInterface, 1: ArgumentBuilderInterface}>
     */
    private array $setupArgBuilders;

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
        unset($this->setupArgBuilders);
        $this->setupInternal($method, ...$argument);

        return $this;
    }

    public function setupImmutable(string $method, mixed ...$argument): static
    {
        unset($this->setupArgBuilders);
        $this->setupImmutableInternal($method, ...$argument);

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

    public function setContainer(DiContainerInterface $container): static
    {
        $this->container = $container;

        return $this;
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
     * @return class-string|non-empty-string
     */
    public function getIdentifier(): string
    {
        return is_string($this->definition)
            ? $this->definition
            : $this->reflectionClass->getName();
    }

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function getTags(): array
    {
        try {
            // 🚩 PHP attributes have higher priority than PHP definitions (see documentation.)
            return $this->getTagsByAttribute() + $this->getTagsInternal();
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot get tags on class "%s".', $this->getIdentifier()),
                previous: $e,
            );
        }
    }

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function hasTag(string $name): bool
    {
        try {
            return isset($this->getTags()[$name]);
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot check exist tag "%s" on class "%s".', $name, $this->getIdentifier()),
                previous: $e,
            );
        }
    }

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function getTag(string $name): ?array
    {
        try {
            return $this->getTags()[$name] ?? null;
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot get tag "%s" on class "%s".', $name, $this->getIdentifier()),
                previous: $e,
            );
        }
    }

    /**
     * @param non-empty-string $name
     * @param TagOptions       $operationOptions
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function geTagPriority(string $name, array $operationOptions = []): int|string|null
    {
        if (null !== ($priority = $this->geTagPriorityInternal($name, $operationOptions))) {
            return $priority;
        }

        $tagOptions = $operationOptions + ($this->getTag($name) ?? []);

        if (isset($tagOptions['priority.method'])) {
            $method = $tagOptions['priority.method'];

            if (!is_string($method) || '' === trim($method)) {
                $wherePriorityMethod = isset($this->getTagsInternal()[$name]['priority.method'])
                    ? 'value with key "priority.method" in the $options parameter in '.DiDefinitionTagArgumentInterface::class.'::bindTag()'
                    : 'the $priorityMethod parameter or the value with key "priority.method" in the $options parameter in the php attribute #[Tag]';

                throw new DiDefinitionException(
                    sprintf('Cannot get tag priority for tag "%s" via method in class %s. The name of the priority method is specified by %s. Priority method must be present none-empty string. Got: %s', $name, $this->getIdentifier(), $wherePriorityMethod, var_export($method, true))
                );
            }

            try {
                return $this->getTagPriorityFromMethod($method, $name, $tagOptions);
            } catch (AutowireException|InvalidArgumentException $e) {
                throw new DiDefinitionException(
                    message: sprintf('Cannot get tag priority for tag "%s" via method %s::%s(). Caused by: %s', $name, $this->getIdentifier(), $method, $e->getMessage()),
                    previous: $e
                );
            }
        }

        // Option (meta-key) only from $operationOptions. It uses through DiDefinitionTaggedAs.
        $priorityDefaultMethod = ($operationOptions['priority.default_method'] ?? null);

        if (!is_string($priorityDefaultMethod)) {
            return null;
        }

        try {
            return $this->getTagPriorityFromMethod($priorityDefaultMethod, $name, $tagOptions);
        } catch (InvalidArgumentException) {
            return null;
        } catch (AutowireException $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot get tag priority for tag "%s" via default priority method %s::%s(). Caused by: %s', $name, $this->getIdentifier(), $priorityDefaultMethod, $e->getMessage()),
                previous: $e
            );
        }
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
     * @throws DiDefinitionExceptionInterface
     */
    private function checkIsInstantiable(): void
    {
        if (!$this->getDefinition()->isInstantiable()) {
            throw new DiDefinitionException(sprintf('The "%s" class is not instantiable.', $this->getDefinition()->getName()));
        }
    }

    /**
     * @return array<non-empty-string, TagOptions>
     *
     * @throws DiDefinitionExceptionInterface
     */
    private function getTagsByAttribute(): array
    {
        if (!$this->getContainer()->getConfig()->isUseAttribute()) {
            return [];
        }

        if (isset($this->tagsByAttribute)) {
            return $this->tagsByAttribute;
        }

        $this->tagsByAttribute = [];

        try {
            $tagAttributes = AttributeReader::getTagAttribute($this->getDefinition());
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot read php attribute #[%s] on class "%s".', Tag::class, $this->getIdentifier()),
                previous: $e,
            );
        }

        foreach ($tagAttributes as $tagAttribute) {
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

    /**
     * @param TagOptions $tagOptions
     *
     * @throws AutowireException|InvalidArgumentException
     */
    private function getTagPriorityFromMethod(string $method, string $tag, array $tagOptions): int|string|null
    {
        $callable = [$this->getIdentifier(), $method];

        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Method must be declared with public and static modifiers.');
        }

        $priority = $callable($tag, $tagOptions);

        if (is_int($priority) || is_string($priority) || is_null($priority)) {
            return $priority;
        }

        throw new AutowireException(
            sprintf('Method must return type "int|string|null" but return type "%s".', get_debug_type($priority))
        );
    }
}

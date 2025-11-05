<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionConfigAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\ArgumentResolverTrait;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiAutowireTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use ReflectionClass;
use ReflectionException;

use function array_map;
use function get_class;
use function get_debug_type;
use function is_object;
use function is_string;
use function sprintf;

/**
 * @phpstan-import-type Tags from DiTaggedDefinitionInterface
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 */
final class DiDefinitionAutowire implements DiDefinitionConfigAutowireInterface, DiDefinitionInvokableInterface, DiDefinitionIdentifierInterface, DiDefinitionAutowireInterface
{
    use AttributeReaderTrait;
    use BindArgumentsTrait {
        bindArguments as private bindArgs;
    }
    use ArgumentResolverTrait;
    use DiContainerTrait;
    use DiAutowireTrait;
    use TagsTrait {
        getTags as private internalGetTags;
        hasTag as private internalHasTag;
        geTagPriority as private internalGeTagPriority;
    }

    private ReflectionClass $reflectionClass;

    private ArgumentBuilder|false $constructArgBuilder;

    /**
     * @var array<non-empty-string, array<non-negative-int, ArgumentBuilder>>
     */
    private array $setupArgBuilder = [];

    /**
     * Methods for setup service by PHP definition via setters (mutable or immutable).
     *
     * @var array<non-empty-string, array<non-negative-int, array{0: bool, array<int|string, mixed>}>>
     */
    private array $setup = [];

    /**
     * Methods for setup service by PHP attribute via setters (mutable or immutable).
     *
     * @var array<non-empty-string, array<non-negative-int, array{0: bool, array<int|string, mixed>}>>
     */
    private array $setupByAttributes;

    /**
     * Tags from php attributes on class.
     *
     * @var array<non-empty-string, TagOptions>
     */
    private array $tagsByAttribute;

    /**
     * @param class-string|ReflectionClass $definition
     */
    public function __construct(private ReflectionClass|string $definition, private ?bool $isSingleton = null)
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
        $this->setup[$method][] = [false, $argument];
        unset($this->setupArgBuilder[$method]);

        return $this;
    }

    public function bindArguments(mixed ...$argument): static
    {
        $this->bindArgs(...$argument);
        unset($this->constructArgBuilder);

        return $this;
    }

    public function setupImmutable(string $method, mixed ...$argument): static
    {
        $this->setup[$method][] = [true, $argument];
        unset($this->setupArgBuilder[$method]);

        return $this;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function invoke(): mixed
    {
        if (!$this->getDefinition()->isInstantiable()) {
            throw new AutowireException(
                sprintf('The "%s" class is not instantiable.', $this->getDefinition()->getName())
            );
        }

        if (!isset($this->constructArgBuilder)) {
            $constructor = $this->getDefinition()->getConstructor();
            $this->constructArgBuilder = match (null) {
                $constructor => false,
                default => new ArgumentBuilder($this->getBindArguments(), $constructor, $this->getContainer())
            };
        }

        if (false === $this->constructArgBuilder) {
            // @var object $object
            $object = $this->getDefinition()->newInstanceWithoutConstructor();
        } else {
            $args = (bool) $this->getContainer()->getConfig()?->isUseAttribute()
                ? $this->constructArgBuilder->basedOnPhpAttributes()
                : $this->constructArgBuilder->basedOnBindArguments();

            /**
             * @var object $object
             */
            $object = $this->getDefinition()->newInstanceArgs($this->resolveArguments($args));
        }

        if ([] === $this->setup && [] === $this->getSetupByAttribute()) {
            return $object;
        }

        // 🚩 Php-attribute override existing setter method defined by <self::setup()> or <self::setupImmutable()> (see documentation.)
        $setupMerged = $this->getSetupByAttribute() + $this->setup;

        foreach ($setupMerged as $method => $calls) {
            if (!$this->getDefinition()->hasMethod($method)) {
                throw new AutowireException(sprintf('The setter method "%s::%s()" does not exist.', $this->getDefinition()->getName(), $method));
            }

            $reflectionMethod = $this->getDefinition()->getMethod($method);

            if ($reflectionMethod->isConstructor() || $reflectionMethod->isDestructor()) {
                throw new AutowireException(sprintf('Cannot use %s::%s() as setter.', $this->getDefinition()->name, $method));
            }

            $reflectionMethodParams = $reflectionMethod->getParameters();

            /**
             * @phpstan-var  boolean $isImmutable
             * @phpstan-var  array<non-negative-int|non-empty-string, mixed> $callArguments
             * @phpstan-var non-negative-int $index
             */
            foreach ($calls as $index => [$isImmutable, $callArguments]) {
                $argBuilder = $this->setupArgBuilder[$method][$index] ?? ($this->setupArgBuilder[$method][$index] = new ArgumentBuilder($callArguments, $reflectionMethod, $this->getContainer()));

                $args = (bool) $this->getContainer()->getConfig()?->isUseAttribute()
                    ? $argBuilder->basedOnBindArgumentsAsPriorityAndPhpAttributes()
                    : $argBuilder->basedOnBindArguments();

                if (!$isImmutable) {
                    $reflectionMethod->invokeArgs($object, $this->resolveArguments($args));

                    continue;
                }

                $result = $reflectionMethod->invokeArgs($object, $this->resolveArguments($args));

                if (is_object($result) && get_class($result) === get_class($object)) {
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
        return $this->getTagsByAttribute() + $this->internalGetTags();
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
        if (null !== ($priority = $this->internalGeTagPriority($name, $operationOptions))) {
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

    /**
     * @return array<non-empty-string, array<non-negative-int, array{0: bool, array<int|string, mixed>}>>
     */
    private function getSetupByAttribute(): array
    {
        if (false === (bool) $this->getContainer()->getConfig()?->isUseAttribute()) {
            return [];
        }

        if (isset($this->setupByAttributes)) {
            return $this->setupByAttributes;
        }

        $this->setupByAttributes = [];

        foreach ($this->getSetupAttribute($this->getDefinition()) as $setupAttr) {
            $this->setupByAttributes[$setupAttr->getIdentifier()][] = [
                $setupAttr->isImmutable(),
                array_map(
                    static fn (mixed $arg) => self::convertStringArgumentToDiDefinitionGet($arg),
                    $setupAttr->getArguments()
                ),
            ];
        }

        return $this->setupByAttributes;
    }
}

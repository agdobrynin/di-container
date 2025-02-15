<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionConfigAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\StaticMethodInTaggedDefinitionTrait;
use Kaspi\DiContainer\Traits\TagsTrait;

/**
 * @phpstan-import-type Tags from DiTaggedDefinitionInterface
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 */
final class DiDefinitionAutowire implements DiDefinitionConfigAutowireInterface, DiDefinitionInvokableInterface, DiDefinitionIdentifierInterface, DiTaggedDefinitionAutowireInterface
{
    use AttributeReaderTrait;
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    use DiContainerTrait;
    use StaticMethodInTaggedDefinitionTrait;
    use TagsTrait {
        getTags as private internalGetTags;
        hasTag as private internalHasTag;
        getTag as private internalGetTag;
        geTagPriority as private internalGeTagPriority;
    }

    private \ReflectionClass $reflectionClass;

    /**
     * @var \ReflectionParameter[]
     */
    private array $reflectionConstructorParams;

    /**
     * @var array<non-empty-string, list<\ReflectionParameter>>
     */
    private array $reflectionMethodParams;

    /**
     * Methods for setup service via setters.
     *
     * @var array<non-empty-string, array<non-empty-string|non-negative-int, mixed>>
     */
    private array $setup = [];

    /**
     * Php attributes on class.
     *
     * @var Tag[]
     */
    private array $tagAttributes;

    /**
     * @param class-string|\ReflectionClass $definition
     */
    public function __construct(private \ReflectionClass|string $definition, private ?bool $isSingleton = null)
    {
        if ($this->definition instanceof \ReflectionClass) {
            $this->reflectionClass = $this->definition;
        }
    }

    /**
     * @return $this
     */
    public function setup(string $method, mixed ...$argument): static
    {
        $this->setup[$method][] = $argument;

        return $this;
    }

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function invoke(): mixed
    {
        /**
         * @var object $object
         */
        $object = [] === $this->getConstructorParams()
            ? $this->getDefinition()->newInstanceWithoutConstructor()
            : $this->getDefinition()->newInstanceArgs($this->resolveParameters($this->getBindArguments(), $this->getConstructorParams()));

        if ([] === $this->setup) {
            return $object;
        }

        foreach ($this->setup as $method => $arguments) {
            if (!$this->getDefinition()->hasMethod($method)) {
                throw new AutowireException(\sprintf('The method "%s" does not exist', $method));
            }

            $this->reflectionMethodParams[$method] ??= $this->getDefinition()->getMethod($method)->getParameters();

            foreach ($arguments as $argument) {
                /**
                 * @phpstan-var array<non-negative-int|non-empty-string, mixed> $argument
                 */
                $args = $this->resolveParameters($argument, $this->reflectionMethodParams[$method]);
                $this->getDefinition()->getMethod($method)->invokeArgs($object, $args);
            }
        }

        return $object;
    }

    public function getDefinition(): \ReflectionClass
    {
        try {
            return $this->reflectionClass ??= new \ReflectionClass($this->definition);
        } catch (\ReflectionException $e) { // @phpstan-ignore catch.neverThrown
            throw new AutowireException(message: $e->getMessage());
        }
    }

    /**
     * @return class-string|non-empty-string
     */
    public function getIdentifier(): string
    {
        return \is_string($this->definition)
            ? $this->definition
            : $this->reflectionClass->getName();
    }

    public function getTags(): array
    {
        $this->attemptsReadTagAttribute();

        return $this->internalGetTags();
    }

    public function hasTag(string $name): bool
    {
        $this->attemptsReadTagAttribute();

        return $this->internalHasTag($name);
    }

    public function getTag(string $name): ?array
    {
        $this->attemptsReadTagAttribute();

        return $this->internalGetTag($name);
    }

    /**
     * @param non-empty-string $name
     * @param TagOptions       $operationOptions
     */
    public function geTagPriority(string $name, array $operationOptions = []): null|int|string
    {
        if (null !== ($priority = $this->internalGeTagPriority($name))) {
            return $priority;
        }

        $this->attemptsReadTagAttribute();

        if ([] !== ($tagOptions = $this->getTag($name)) && isset($tagOptions['priority.method'])) {
            $tagOptions = $this->getTag($name) + $operationOptions;
            $howGetPriority = \sprintf('Get priority by option "priority.method" for tag "%s".', $name);

            // @phpstan-ignore return.type
            return self::invokeMethod($this, $tagOptions['priority.method'], true, $howGetPriority, ['int', 'string', 'null'], $name, $tagOptions);
        }

        $priorityDefaultMethod = ($operationOptions['priority.default_method'] ?? null);

        if (null !== $priorityDefaultMethod) {
            $tagOptions = $operationOptions + ($this->getTag($name) ?? []);
            $howGetPriority = \sprintf('Get priority by option "priority.default_method" for class "%s".', $this->getDefinition()->getName());

            // @phpstan-ignore return.type
            return self::invokeMethod($this, $priorityDefaultMethod, false, $howGetPriority, ['int', 'string', 'null'], $name, $tagOptions);
        }

        return null;
    }

    private function attemptsReadTagAttribute(): void
    {
        // @phpstan-ignore booleanAnd.rightNotBoolean
        if (!isset($this->tagAttributes) && $this->getContainer()->getConfig()?->isUseAttribute()) {
            $this->tagAttributes = [];

            foreach ($this->getTagAttribute($this->getDefinition()) as $tagAttribute) {
                $this->tagAttributes[] = $tagAttribute;
                // 🚩 Php-attribute override existing tag defined by <bindTag> (see documentation.)
                $this->bindTag(
                    name: $tagAttribute->getIdentifier(),
                    options: (null !== $tagAttribute->getPriorityMethod() ? ['priority.method' => $tagAttribute->getPriorityMethod()] : []) + $tagAttribute->getOptions(),
                    priority: $tagAttribute->getPriority(),
                );
            }
        }
    }

    /**
     * @return \ReflectionParameter[]
     */
    private function getConstructorParams(): array
    {
        if (isset($this->reflectionConstructorParams)) {
            return $this->reflectionConstructorParams;
        }

        $reflectionClass = $this->getDefinition();

        if (!$reflectionClass->isInstantiable()) {
            throw new AutowireException(
                \sprintf('The [%s] class is not instantiable', $reflectionClass->getName())
            );
        }

        return $this->reflectionConstructorParams = $reflectionClass->getConstructor()?->getParameters() ?? [];
    }
}

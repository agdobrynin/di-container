<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedDefaultPriorityMethod;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\TagsTrait;

final class DiDefinitionAutowire implements DiDefinitionSetupInterface, DiDefinitionInvokableInterface, DiDefinitionIdentifierInterface, DiTaggedDefinitionInterface
{
    use AttributeReaderTrait;
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    use DiContainerTrait;
    use TagsTrait {
        getTags as private internalGetTags;
        hasTag as private internalHasTag;
        geTagPriority as private internalGeTagPriority;
        bindTag as private internalBindTag;
    }

    private \ReflectionClass $reflectionClass;

    /**
     * @var \ReflectionParameter[]
     */
    private array $reflectionConstructorParams;

    /**
     * @phan-suppress PhanReadOnlyPrivateProperty
     *
     * @var array<non-empty-string, array<int, \ReflectionParameter>>
     */
    private array $reflectionMethodParams;

    /**
     * Methods for setup service via setters.
     *
     * @var array<non-empty-string, array<int|non-empty-string, mixed>>
     */
    private array $setup = [];

    /**
     * Php attributes on class.
     *
     * @var Tag|TaggedDefaultPriorityMethod[]
     */
    private array $tagAttributes;

    /**
     * When priority option in tag not defined this method will return priority value from class.
     */
    private ?string $defaultPriorityTaggedMethod = null;

    public function __construct(private \ReflectionClass|string $definition, private ?bool $isSingleton = null)
    {
        if ($this->definition instanceof \ReflectionClass) {
            $this->reflectionClass = $this->definition;
        }
    }

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
                $this->getDefinition()->getMethod($method)
                    ->invokeArgs($object, $this->resolveParameters($argument, $this->reflectionMethodParams[$method]))
                ;
            }
        }

        return $object;
    }

    /**
     * @throws AutowireExceptionInterface
     */
    public function getDefinition(): \ReflectionClass
    {
        try {
            return $this->reflectionClass ??= new \ReflectionClass($this->definition);
        } catch (\ReflectionException $e) {
            throw new AutowireException(message: $e->getMessage());
        }
    }

    public function getIdentifier(): string
    {
        return \is_string($this->definition)
            ? $this->definition
            : $this->reflectionClass->getName();
    }

    public function bindTag(string $name, array $options = [], null|int|string $priority = null, ?string $priorityTaggedMethod = null): static
    {
        if (null !== $priorityTaggedMethod) {
            $options['priorityTaggedMethod'] = $priorityTaggedMethod;
        }

        $this->internalBindTag($name, $options, $priority);

        return $this;
    }

    public function bindTaggedDefaultPriorityMethod(?string $priorityTaggedMethod): static
    {
        $this->defaultPriorityTaggedMethod = $priorityTaggedMethod;

        return $this;
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

    public function geTagPriority(string $name): null|int|string
    {
        if (null !== ($priority = $this->internalGeTagPriority($name))) {
            return $priority;
        }

        $this->attemptsReadTagAttribute();
        $tagOptions = $this->getTag($name);

        if ($tagOptions && isset($tagOptions['priorityTaggedMethod'])) {
            $priorityTaggedMethod = $tagOptions['priorityTaggedMethod'];

            if (!\is_string($priorityTaggedMethod) || '' === \trim($priorityTaggedMethod)) {
                throw new AutowireException(
                    \sprintf('The option "priorityTaggedMethod" must be non-empty string for tag "%s"', $name)
                );
            }

            return $this->invokePriorityMethod($priorityTaggedMethod, \sprintf('The option "priorityTaggedMethod" for tag "%s"', $name));
        }

        if (null !== $this->defaultPriorityTaggedMethod) {
            if ('' === \trim($this->defaultPriorityTaggedMethod)) {
                throw new AutowireException(
                    \sprintf('The "defaultPriorityTaggedMethod" must be non-empty string for "%s"', $this->getDefinition()->getName())
                );
            }

            return $this->invokePriorityMethod($this->defaultPriorityTaggedMethod, \sprintf('The "defaultPriorityTaggedMethod" for "%s"', $this->getDefinition()->getName()));
        }

        return null;
    }

    private function invokePriorityMethod(string $priorityMethod, string $whereMethod): null|int|string
    {
        if (!$this->getDefinition()->hasMethod($priorityMethod)) {
            throw new AutowireException(\sprintf('%s but method "%s" does not exist', $whereMethod, $priorityMethod));
        }

        $method = $this->getDefinition()->getMethod($priorityMethod);

        if (!$method->isPublic()) {
            throw new AutowireException(\sprintf('%s but method "%s" must be declared as public', $whereMethod, $priorityMethod));
        }

        if (!$method->isStatic()) {
            throw new AutowireException(\sprintf('%s but method "%s" must be declared as static', $whereMethod, $priorityMethod));
        }

        $rt = $method->getReturnType();

        $types = match (true) {
            $rt instanceof \ReflectionNamedType => [$rt->getName()],
            $rt instanceof \ReflectionUnionType => \array_map(static fn ($type) => $type->getName(), $rt->getTypes()),
            default => null,
        };

        if (null === $types || \array_diff($types, ['string', 'int', 'null'])) {
            throw new AutowireException(
                \sprintf('%s method "%s" must return types string, int or null. Got return type: %s', $whereMethod, $priorityMethod, \implode(', ', $types))
            );
        }

        return $method->invoke(null);
    }

    private function attemptsReadTagAttribute(): void
    {
        if (!isset($this->tagAttributes) && $this->getContainer()->getConfig()?->isUseAttribute()) {
            $this->tagAttributes = [];

            foreach ($this->getTagAttribute($this->getDefinition()) as $tagAttribute) {
                $this->tagAttributes[] = $tagAttribute;
                // 🚩 Php-attribute override existing tag defined by <bindTag> (see documentation.)
                $this->bindTag(
                    $tagAttribute->getIdentifier(),
                    $tagAttribute->getOptions(),
                    $tagAttribute->getPriority(),
                    $tagAttribute->getPriorityTaggedMethod()
                );
            }

            if ($defaultPriorityTaggedMethod = $this->getTagDefaultPriorityTaggedMethod($this->getDefinition())) {
                $this->tagAttributes[] = $defaultPriorityTaggedMethod;
                $this->defaultPriorityTaggedMethod = $defaultPriorityTaggedMethod->getIdentifier();
            }
        }
    }

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

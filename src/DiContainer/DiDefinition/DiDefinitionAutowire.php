<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Attributes\Tag;
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
     * @var Tag[]
     */
    private array $tagAttributes;

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

    public function bindTag(string $name, array $options = [], null|int|string $priority = null, ?string $priorityTagMethod = null): static
    {
        if (null !== $priorityTagMethod) {
            $options['priorityTagMethod'] = $priorityTagMethod;
        }

        $this->internalBindTag($name, $options, $priority);

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

    public function geTagPriority(string $name, ?string $defaultPriorityTagMethod = null): null|int|string
    {
        if (null !== ($priority = $this->internalGeTagPriority($name))) {
            return $priority;
        }

        $this->attemptsReadTagAttribute();
        $tagOptions = $this->getTag($name);

        if ($tagOptions && isset($tagOptions['priorityTagMethod'])) {
            $priorityTagMethod = $tagOptions['priorityTagMethod'];
            $howGetPriority = \sprintf('Get priority by option "priorityTagMethod" for tag "%s"', $name);

            if (!\is_string($priorityTagMethod) || '' === \trim($priorityTagMethod)) {
                throw new AutowireException($howGetPriority.'. The value option must be non-empty string.');
            }

            return $this->invokePriorityMethod($priorityTagMethod, $howGetPriority);
        }

        if (null !== $defaultPriorityTagMethod
            && $this->getDefinition()->hasMethod($defaultPriorityTagMethod)) {
            return $this->invokePriorityMethod(
                $defaultPriorityTagMethod,
                \sprintf('Get priority by option "defaultPriorityTagMethod" for class "%s"', $this->getDefinition()->getName())
            );
        }

        return null;
    }

    /**
     * @param non-empty-string $priorityMethod
     */
    private function invokePriorityMethod(string $priorityMethod, string $howGetPriority): null|int|string
    {
        $reflectionClass = $this->getDefinition();

        if (!$reflectionClass->isInstantiable()) {
            throw new AutowireException(
                \sprintf('%s. "%s" is not instantiable.', $howGetPriority, $reflectionClass->getName())
            );
        }

        if (!$reflectionClass->hasMethod($priorityMethod)) {
            throw new AutowireException(
                \sprintf('%s. "%s::%s()" does not exist.', $howGetPriority, $reflectionClass->getName(), $priorityMethod)
            );
        }

        $method = $reflectionClass->getMethod($priorityMethod);

        if (!$method->isPublic()) {
            throw new AutowireException(
                \sprintf('%s. "%s::%s()" must be declared as public.', $howGetPriority, $reflectionClass->getName(), $priorityMethod)
            );
        }

        if (!$method->isStatic()) {
            throw new AutowireException(
                \sprintf('%s. "%s::%s()" must be declared as static.', $howGetPriority, $reflectionClass->getName(), $priorityMethod)
            );
        }

        $rt = $method->getReturnType();

        $types = match (true) {
            $rt instanceof \ReflectionNamedType => [$rt->getName()],
            $rt instanceof \ReflectionUnionType => \array_map(static fn ($type) => $type->getName(), $rt->getTypes()),
            default => null,
        };

        if (null === $types || \array_diff($types, ['string', 'int', 'null'])) {
            $message = \sprintf(
                '%s. "%s::%s()" must return types string, int or null. Got return type: %s',
                $howGetPriority,
                $reflectionClass->getName(),
                $priorityMethod,
                \implode(', ', $types)
            );

            throw new AutowireException($message);
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
                    $tagAttribute->getPriorityTagMethod()
                );
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

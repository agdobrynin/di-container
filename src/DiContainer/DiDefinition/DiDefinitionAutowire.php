<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionConfigAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\TagsTrait;

use function Kaspi\DiContainer\tagOptions;

final class DiDefinitionAutowire implements DiDefinitionConfigAutowireInterface, DiDefinitionInvokableInterface, DiDefinitionIdentifierInterface, DiTaggedDefinitionAutowireInterface
{
    use AttributeReaderTrait;
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    use DiContainerTrait;
    use TagsTrait {
        getTags as private internalGetTags;
        hasTag as private internalHasTag;
        geTagPriority as private internalGeTagPriority;
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

    public function geTagPriority(string $name, array $operationOptions = []): null|int|string
    {
        if (null !== ($priority = $this->internalGeTagPriority($name))) {
            return $priority;
        }

        $this->attemptsReadTagAttribute();
        $tagOptions = $operationOptions + ($this->getTag($name) ?? []);

        if ($tagOptions && isset($tagOptions['priorityMethod'])) {
            $priorityTagMethodFromOptions = $tagOptions['priorityMethod'];
            $howGetPriority = \sprintf('Get priority by option "priorityMethod" for tag "%s".', $name);

            return $this->invokePriorityMethod($priorityTagMethodFromOptions, true, [$name => $tagOptions], $howGetPriority);
        }

        $defaultPriorityMethod = ($tagOptions['defaultPriorityMethod'] ?? null);

        if (null !== $defaultPriorityMethod) {
            $howGetPriority = \sprintf('Get priority by option "defaultPriorityMethod" for class "%s".', $this->getDefinition()->getName());
            $defaultPriorityMethodIsRequired = (bool) ($tagOptions['defaultPriorityMethodIsRequired'] ?? null);

            return $this->invokePriorityMethod($defaultPriorityMethod, $defaultPriorityMethodIsRequired, [$name => $tagOptions], $howGetPriority);
        }

        return null;
    }

    private function invokePriorityMethod(mixed $priorityMethod, bool $requirePriorityMethod, array $tagWithOptions, string $howGetPriority): null|int|string
    {
        if (!\is_string($priorityMethod) || '' === \trim($priorityMethod)) {
            throw new AutowireException($howGetPriority.' The value option must be non-empty string.');
        }

        $reflectionClass = $this->getDefinition();
        $isCallable = \is_callable([$reflectionClass->name, $priorityMethod]);
        $supportReturnTypes = ['int', 'string', 'null'];

        if (!$isCallable || ($types = $this->diffReturnType($reflectionClass->getMethod($priorityMethod), ...$supportReturnTypes))) {
            if (!$requirePriorityMethod) {
                return null;
            }

            $message = \sprintf(
                '%s "%s::%s()" method must be declared with public and static modifiers. Return type must be %s.%s',
                $howGetPriority,
                $reflectionClass->getName(),
                $priorityMethod,
                \implode(', ', $supportReturnTypes),
                isset($types) ? ' Got return type: '.\implode(', ', $types) : ''
            );

            throw new AutowireException($message);
        }

        return \call_user_func([$reflectionClass->name, $priorityMethod], $tagWithOptions);
    }

    private function diffReturnType(\ReflectionMethod $reflectionMethod, string ...$type): array
    {
        $rt = $reflectionMethod->getReturnType();

        $types = match (true) {
            $rt instanceof \ReflectionNamedType => [$rt->getName()],
            $rt instanceof \ReflectionUnionType => \array_map(static fn ($t) => $t->getName(), $rt->getTypes()),
            default => ['undefined'],
        };

        return \array_diff($types, $type);
    }

    private function attemptsReadTagAttribute(): void
    {
        if (!isset($this->tagAttributes) && $this->getContainer()->getConfig()?->isUseAttribute()) {
            $this->tagAttributes = [];

            foreach ($this->getTagAttribute($this->getDefinition()) as $tagAttribute) {
                $this->tagAttributes[] = $tagAttribute;
                // 🚩 Php-attribute override existing tag defined by <bindTag> (see documentation.)
                $this->bindTag(
                    name: $tagAttribute->getIdentifier(),
                    options: tagOptions(priorityMethod: $tagAttribute->getPriorityMethod()) + $tagAttribute->getOptions(),
                    priority: $tagAttribute->getPriority(),
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

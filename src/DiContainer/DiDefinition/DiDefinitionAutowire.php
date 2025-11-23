<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use InvalidArgumentException;
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
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\SetupConfigureTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionException;
use Throwable;

use function get_class;
use function get_debug_type;
use function is_callable;
use function is_int;
use function is_null;
use function is_object;
use function is_string;
use function Kaspi\DiContainer\functionName;
use function sprintf;
use function trim;
use function var_export;

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
        unset($this->constructArgBuilder);
        $this->bindArgumentsInternal(...$argument);

        return $this;
    }

    public function setupImmutable(string $method, mixed ...$argument): static
    {
        unset($this->setupArgBuilder[$method]);
        $this->setupImmutableInternal($method, ...$argument);

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
            try {
                $reflectionMethod = $this->getDefinition()->getMethod($method);
            } catch (ReflectionException $e) {
                throw $this->exceptionWhenClassExist(
                    message: sprintf('The setter method "%s::%s()" does not exist.', $this->getDefinition()->getName(), $method),
                    previous: $e
                );
            }

            if ($reflectionMethod->isConstructor() || $reflectionMethod->isDestructor()) {
                throw new DiDefinitionException(sprintf('Cannot use %s::%s() as setter.', $this->getDefinition()->name, $method));
            }

            foreach ($calls as $index => [$setupConfigureType, $callArguments]) {
                $argBuilder = $this->setupArgBuilder[$method][$index] ??= new ArgumentBuilder($callArguments, $reflectionMethod, $this->getContainer());

                $resolvedArguments = [];

                foreach ($argBuilder->buildByPriorityBindArguments() as $argNameOrIndex => $arg) {
                    try {
                        $resolvedArguments[$argNameOrIndex] = $arg instanceof DiDefinitionInterface
                            ? $arg->resolve($container, $this)
                            : $arg;
                    } catch (ContainerExceptionInterface $e) {
                        throw $this->exceptionWhenClassExist(
                            message: $this->exceptionMessageWhenResolveArgument($argNameOrIndex, $argBuilder),
                            previous: $e,
                            context_argument: $arg
                        );
                    }
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

                throw $this->exceptionWhenClassExist(
                    message: sprintf(
                        'The immutable setter "%s::%s()" must return same class "%s". Got type: %s',
                        $this->getDefinition()->getName(),
                        $method,
                        $this->getDefinition()->getName(),
                        get_debug_type($result)
                    ),
                    context_method_result: $result
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
            throw (new DiDefinitionException(message: $e->getMessage()))
                ->setContext(context_definition: $this->definition)
            ;
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
            $method = $tagOptions['priority.method'];

            if (!is_string($method) || '' === trim($method)) {
                $wherePriorityMethod = isset($this->getTagsInternal()[$name]['priority.method'])
                    ? 'value with key "priority.method" in the $options parameter in '.DiDefinitionTagArgumentInterface::class.'::bindTag()'
                    : 'the $priorityMethod parameter or the value with key "priority.method" in the $options parameter in the php attribute #[Tag]';

                throw (
                    new DiDefinitionException(
                        sprintf('Cannot get tag priority for tag "%s" via method in class %s. The name of the priority method is specified by %s. Priority method must be present none-empty string. Got: %s', $name, $this->getIdentifier(), $wherePriorityMethod, var_export($method, true))
                    )
                )
                    ->setContext(context_tag_options: $tagOptions)
                ;
            }

            try {
                return $this->getTagPriorityFromMethod($method, $name, $tagOptions);
            } catch (AutowireException|InvalidArgumentException $e) {
                throw (
                    new DiDefinitionException(
                        message: sprintf('Cannot get tag priority for tag "%s" via method %s::%s().', $name, $this->getIdentifier(), $method),
                        previous: $e
                    )
                )
                    ->setContext(
                        context_callable: [$this->getIdentifier(), $method],
                        context_operation_options: $operationOptions,
                        context_tag_options: $tagOptions
                    )
                ;
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
            throw (
                new DiDefinitionException(
                    message: sprintf('Cannot get tag priority for tag "%s" via default priority method %s::%s().', $name, $this->getIdentifier(), $priorityDefaultMethod),
                    previous: $e
                )
            )
                ->setContext(
                    context_callable: [$this->getIdentifier(), $priorityDefaultMethod],
                    context_operation_options: $operationOptions,
                    context_tag_options: $tagOptions
                )
            ;
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
     * @throws ArgumentBuilderExceptionInterface|DiDefinitionExceptionInterface
     */
    private function newInstance(): object
    {
        if (!$this->getDefinition()->isInstantiable()) {
            throw $this->exceptionWhenClassExist(sprintf('The "%s" class is not instantiable.', $this->getDefinition()->getName()));
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
            try {
                $resolvedArguments[$argNameOrIndex] = $arg instanceof DiDefinitionInterface
                    ? $arg->resolve($this->getContainer(), $this)
                    : $arg;
            } catch (ContainerExceptionInterface $e) {
                throw $this->exceptionWhenClassExist(
                    message: $this->exceptionMessageWhenResolveArgument($argNameOrIndex, $this->constructArgBuilder),
                    previous: $e,
                    context_argument: $arg
                );
            }
        }

        return $this->getDefinition()->newInstanceArgs($resolvedArguments);
    }

    /**
     * @return array<non-empty-string, TagOptions>
     *
     * @throws DiDefinitionExceptionInterface
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

    private function exceptionMessageWhenResolveArgument(int|string $argNameOrIndex, ArgumentBuilder $argBuilder): string
    {
        $argMessage = is_int($argPresentedBy = $argBuilder->getArgumentNameOrIndexFromBindArguments($argNameOrIndex))
            ? sprintf('at position #%d', $argPresentedBy)
            : sprintf('by named argument $%s', $argPresentedBy);

        return sprintf('Cannot resolve parameter %s in %s.', $argMessage, functionName($argBuilder->getFunctionOrMethod()));
    }

    private function exceptionWhenClassExist(string $message, ?Throwable $previous = null, mixed ...$context): DiDefinitionException
    {
        return (new DiDefinitionException(message: $message, previous: $previous))
            ->setContext(
                ...$context,
                context_reflection_class: $this->getDefinition(),
            )
        ;
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
            sprintf('Method must return type "int|string|null" but return type "%s".', get_debug_type($priority)),
        );
    }
}

<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionConfigAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\DiDefinitionAutowireTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

use function array_map;
use function get_class;
use function get_debug_type;
use function in_array;
use function is_object;
use function is_string;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function substr;

/**
 * @phpstan-import-type Tags from DiTaggedDefinitionInterface
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 */
final class DiDefinitionAutowire implements DiDefinitionConfigAutowireInterface, DiDefinitionInvokableInterface, DiDefinitionIdentifierInterface, DiDefinitionAutowireInterface
{
    use AttributeReaderTrait;
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    use DiContainerTrait;
    use DiDefinitionAutowireTrait;
    use TagsTrait {
        getTags as private internalGetTags;
        hasTag as private internalHasTag;
        geTagPriority as private internalGeTagPriority;
    }

    private ReflectionClass $reflectionClass;

    /**
     * @var array<non-empty-string, array{args: ReflectionParameter[], returnType: string}>
     */
    private array $reflectionMethodMeta;

    /**
     * Methods for setup service via setters (mutable or immutable).
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
     * Php attributes on class.
     *
     * @var Tag[]
     */
    private array $tagAttributes;

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

        return $this;
    }

    public function setupImmutable(string $method, mixed ...$argument): static
    {
        $this->setup[$method][] = [true, $argument];

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
            : $this->getDefinition()->newInstanceArgs($this->resolveParameters($this->getBindArguments(), $this->getConstructorParams(), true));

        // Check setter methods.
        $this->setupByAttributes ??= $this->attemptsReadSetupAttribute();

        if ([] === $this->setup && [] === $this->setupByAttributes) {
            return $object;
        }

        // prepare metadata for calling setter methods
        $fullyClassNameLowercase = strtolower($this->getDefinition()->getName());
        $exceptionMessageImmutableSetter = 'The immutable setter "%s::%s()" must return same class "%s". Got type: %s';

        // 🚩 Php-attribute override existing setter method defined by <self::setup()> or <self::setupImmutable()> (see documentation.)
        $setupMerged = $this->setupByAttributes + $this->setup;

        foreach ($setupMerged as $method => $calls) {
            if (!$this->getDefinition()->hasMethod($method)) {
                throw new AutowireException(sprintf('The setter method "%s::%s()" does not exist.', $this->getDefinition()->getName(), $method));
            }

            $reflectionMethod = $this->getDefinition()->getMethod($method);

            if ($reflectionMethod->isConstructor() || $reflectionMethod->isDestructor()) {
                throw new AutowireException(sprintf('Cannot use %s::%s() as setter.', $this->getDefinition()->name, $method));
            }

            $reflectionMethod = $this->getDefinition()->getMethod($method);

            $this->reflectionMethodMeta[$method] ??= [
                'args' => $reflectionMethod->getParameters(),
                'returnType' => strtolower((string) $reflectionMethod->getReturnType()),
            ];

            /**
             * @phpstan-var  boolean $isImmutable
             * @phpstan-var  array<non-negative-int|non-empty-string, mixed> $callArguments
             */
            foreach ($calls as [$isImmutable, $callArguments]) {
                $reflectionParameters = $this->reflectionMethodMeta[$method]['args'];

                if (!$isImmutable) {
                    $reflectionMethod->invokeArgs($object, $this->resolveParameters($callArguments, $reflectionParameters, false));

                    continue;
                }

                // check return type before invoke method with argument
                $returnType = $this->reflectionMethodMeta[$method]['returnType'];

                if ('' !== $returnType && $fullyClassNameLowercase !== $returnType && !in_array($returnType, ['self', 'static'], true)) {
                    throw new AutowireException(sprintf($exceptionMessageImmutableSetter, $this->getDefinition()->getName(), $method, $this->getDefinition()->getName(), $fullyClassNameLowercase));
                }

                $result = $reflectionMethod->invokeArgs($object, $this->resolveParameters($callArguments, $reflectionParameters, false));

                if (is_object($result) && get_class($result) === get_class($object)) {
                    $object = $result;
                    unset($result);

                    continue;
                }

                throw new AutowireException(sprintf($exceptionMessageImmutableSetter, $this->getDefinition()->getName(), $method, $this->getDefinition()->getName(), get_debug_type($result)));
            }
        }

        return $object;
    }

    public function getDefinition(): ReflectionClass
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
        $this->attemptsReadTagAttribute();

        return $this->internalGetTags();
    }

    public function hasTag(string $name): bool
    {
        $this->attemptsReadTagAttribute();

        return $this->internalHasTag($name);
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
            $howGetPriority = sprintf('Get priority by option "priority.method" for tag "%s".', $name);

            // @phpstan-ignore return.type
            return self::callStaticMethod($this, $tagOptions['priority.method'], true, $howGetPriority, ['int', 'string', 'null'], $name, $tagOptions);
        }

        $priorityDefaultMethod = ($operationOptions['priority.default_method'] ?? null);

        if (null !== $priorityDefaultMethod) {
            $tagOptions = $operationOptions + ($this->getTag($name) ?? []);
            $howGetPriority = sprintf('Get priority by option "priority.default_method" for class "%s".', $this->getDefinition()->getName());

            // @phpstan-ignore return.type
            return self::callStaticMethod($this, $priorityDefaultMethod, false, $howGetPriority, ['int', 'string', 'null'], $name, $tagOptions);
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
     * @return array<non-empty-string, array<non-negative-int, array{0: bool, array<int|string, mixed>}>>
     */
    private function attemptsReadSetupAttribute(): array
    {
        if (false === (bool) $this->getContainer()->getConfig()?->isUseAttribute()) {
            return [];
        }

        $setupByAttributes = [];

        foreach ($this->getSetupAttribute($this->getDefinition()) as $setupAttr) {
            /**
             * Convention for string value in argument:
             *  - 'raw str' raw value, as is
             * - '@container-identifier' convert to new DiDefinitionGet('container-identifier')
             * - '@@container-identifier' convert to string '@container-identifier'
             */
            $convertedArgs = array_map(static function (mixed $arg) {
                if (is_string($arg) && str_starts_with($arg, '@')) {
                    return match (true) {
                        str_starts_with($arg, '@@') => substr($arg, 1),
                        '' !== ($id = substr($arg, 1)) => new DiDefinitionGet($id),
                        default => $arg
                    };
                }

                return $arg;
            }, $setupAttr->getArguments());

            $setupByAttributes[$setupAttr->getIdentifier()][] = [$setupAttr->isImmutable(), $convertedArgs];
        }

        return $setupByAttributes;
    }

    /**
     * @return ReflectionParameter[]
     */
    private function getConstructorParams(): array
    {
        if (isset($this->reflectionMethodMeta['__construct']['args'])) {
            return $this->reflectionMethodMeta['__construct']['args'];
        }

        $reflectionClass = $this->getDefinition();

        if (!$reflectionClass->isInstantiable()) {
            throw new AutowireException(
                sprintf('The "%s" class is not instantiable.', $reflectionClass->getName())
            );
        }

        $this->reflectionMethodMeta['__construct'] = [
            'args' => $reflectionClass->getConstructor()?->getParameters() ?? [],
            'returnType' => 'void',
        ];

        return $this->reflectionMethodMeta['__construct']['args'];
    }
}

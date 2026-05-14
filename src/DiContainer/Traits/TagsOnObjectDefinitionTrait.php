<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use InvalidArgumentException;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use ReflectionClass;
use ReflectionException;

use function get_debug_type;
use function is_callable;
use function is_int;
use function is_null;
use function is_string;
use function sprintf;
use function trim;
use function var_export;

/**
 * @phpstan-import-type Tags from DiTaggedDefinitionInterface
 * @phpstan-import-type TagOptions from DiDefinitionTagArgumentInterface
 */
trait TagsOnObjectDefinitionTrait
{
    use TagsTrait {
        getTags as public getBoundTags;
        hasTag as private hasTagInternal;
        geTagPriority as private geTagPriorityInternal;
    }

    private DiContainerInterface $container;

    /**
     * Tags from php attributes on class.
     *
     * @var array<non-empty-string, TagOptions>
     */
    private array $tagsByAttribute;

    abstract public function getDefinitionIdentifier(): string;

    public function setContainer(DiContainerInterface $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function getTags(): array
    {
        if (!$this->getContainer()->getConfig()->isUseAttribute()) {
            return $this->getBoundTags();
        }

        try {
            // 🚩 PHP attributes have higher priority than PHP definitions (see documentation.)
            return $this->getTagsByAttribute() + $this->getBoundTags();
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot get tags on class "%s".', $this->getDefinitionIdentifier()),
                previous: $e,
            );
        }
    }

    public function hasTag(string $name): bool
    {
        try {
            return isset($this->getTags()[$name]);
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot check exist tag "%s" on class "%s".', $name, $this->getDefinitionIdentifier()),
                previous: $e,
            );
        }
    }

    public function getTag(string $name): ?array
    {
        try {
            return $this->getTags()[$name] ?? null;
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot get tag "%s" on class "%s".', $name, $this->getDefinitionIdentifier()),
                previous: $e,
            );
        }
    }

    public function geTagPriority(string $name, array $operationOptions = []): int|string|null
    {
        if (null !== ($priority = $this->geTagPriorityInternal($name, $operationOptions))) {
            return $priority;
        }

        $tagOptions = $operationOptions + ($this->getTag($name) ?? []);

        if (isset($tagOptions['priority.method'])) {
            $method = $tagOptions['priority.method'];

            if (!is_string($method) || '' === trim($method)) {
                $wherePriorityMethod = isset($this->getBoundTags()[$name]['priority.method'])
                    ? 'value with key "priority.method" into the $options parameter pass via method '.DiDefinitionTagArgumentInterface::class.'::bindTag()'
                    : 'the $priorityMethod parameter or the value with key "priority.method" into the $options parameter via the php attribute #[Tag]';

                throw new DiDefinitionException(
                    sprintf('Cannot get tag priority for tag "%s" via method in class %s. The name of the priority method is specified by %s. Priority method must be present none-empty string. Got: %s', $name, $this->getDefinitionIdentifier(), $wherePriorityMethod, var_export($method, true))
                );
            }

            try {
                return $this->getTagPriorityFromMethod($method, $name, $tagOptions);
            } catch (AutowireException|InvalidArgumentException $e) {
                throw new DiDefinitionException(
                    message: sprintf('Cannot get tag priority for tag "%s" via method %s::%s(). Caused by: %s', $name, $this->getDefinitionIdentifier(), $method, $e->getMessage()),
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
                message: sprintf('Cannot get tag priority for tag "%s" via default priority method %s::%s(). Caused by: %s', $name, $this->getDefinitionIdentifier(), $priorityDefaultMethod, $e->getMessage()),
                previous: $e
            );
        }
    }

    public function getTagsByAttribute(): array
    {
        if (isset($this->tagsByAttribute)) {
            return $this->tagsByAttribute;
        }

        $this->tagsByAttribute = [];

        try {
            $tagAttributes = AttributeReader::getTagAttribute(new ReflectionClass($this->getDefinitionIdentifier()));
        } catch (ReflectionException $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot read php attribute #[%s] on class "%s".', Tag::class, $this->getDefinitionIdentifier()),
                previous: $e,
            );
        }

        foreach ($tagAttributes as $tagAttribute) {
            $priorityMethod = null !== $tagAttribute->priorityMethod
                ? ['priority.method' => $tagAttribute->priorityMethod]
                : [];
            $this->tagsByAttribute[$tagAttribute->name] = self::transformTagOptions(
                $priorityMethod + $tagAttribute->options,
                $tagAttribute->priority
            );
        }

        return $this->tagsByAttribute;
    }

    private function getContainer(): DiContainerInterface
    {
        if (!isset($this->container)) {
            throw new DiDefinitionException(
                sprintf('Need set container implementation. Use method %s::setContainer(). Definition identifier "%s".', self::class, $this->getDefinitionIdentifier())
            );
        }

        return $this->container;
    }

    /**
     * @param TagOptions $tagOptions
     *
     * @throws AutowireException|InvalidArgumentException
     */
    private function getTagPriorityFromMethod(string $method, string $tag, array $tagOptions): int|string|null
    {
        $callable = [$this->getDefinitionIdentifier(), $method];

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

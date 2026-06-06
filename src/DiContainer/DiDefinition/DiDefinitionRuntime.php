<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionRuntimeInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedObjectDefinitionInterface;
use Kaspi\DiContainer\Interfaces\ResetInterface;
use Kaspi\DiContainer\Traits\TagsOnObjectDefinitionTrait;
use ReflectionClass;
use ReflectionException;

use function rtrim;
use function sprintf;
use function var_export;

final class DiDefinitionRuntime implements DiDefinitionRuntimeInterface, DiDefinitionTagArgumentInterface, DiTaggedObjectDefinitionInterface, ResetInterface
{
    use TagsOnObjectDefinitionTrait {
        reset as private resetTrait;
    }

    private object $definition;

    private ReflectionClass $classDefinitionReflection;

    /**
     * @param class-string|non-empty-string $containerIdentifier
     * @param null|class-string             $classDefinition
     */
    public function __construct(
        private readonly string $containerIdentifier,
        private readonly ?string $message = null,
        private readonly ?string $classDefinition = null,
    ) {}

    public function getIdentifier(): string
    {
        return $this->containerIdentifier;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): object
    {
        if (!isset($this->definition)) {
            $additionalMessage = $this->message ?? sprintf('You should replace the value of definition in the runtime container using the method DiContainerInterface::set(%s, $objectInstance).', var_export($this->containerIdentifier, true));

            throw new DiDefinitionException(
                rtrim(
                    sprintf('The runtime definition with container identifier %s cannot be resolved. %s', var_export($this->containerIdentifier, true), $additionalMessage)
                )
            );
        }

        return $this->definition;
    }

    public function getDefinition(): ?object
    {
        return $this->definition ?? null;
    }

    public function setDefinition(object $definition): void
    {
        $this->definition ??= $definition;
    }

    public function isImplementInterface(string $interface): bool
    {
        try {
            $this->classDefinitionReflection ??= new ReflectionClass($this->getDefinitionIdentifier());
        } catch (ReflectionException $e) {
            throw new DiDefinitionException(
                sprintf('You should to be defined a php class through the parameters $containerIdentifier or $classDefinition. Current values: $containerIdentifier %s, $classDefinition %s', var_export($this->containerIdentifier, true), var_export($this->classDefinition, true)),
                previous: $e,
            );
        }

        return $this->classDefinitionReflection->implementsInterface($interface);
    }

    public function getDefinitionIdentifier(): string
    {
        return $this->classDefinition ?? $this->containerIdentifier; // @phpstan-ignore return.type
    }

    public function reset(): void
    {
        $this->resetTrait();

        unset(
            $this->definition,
            $this->classDefinitionReflection,
        );
    }

    protected function readTagAttributes(): Generator
    {
        try {
            $this->classDefinitionReflection ??= new ReflectionClass($this->getDefinitionIdentifier());
            $diRuntimeAttribute = $this->getDiRuntimeAttributeConfiguringDefinition($this->classDefinitionReflection);
        } catch (AutowireAttributeException|ReflectionException $e) {
            throw new DiDefinitionException(
                sprintf('Cannot read php attribute "%s" on class "%s".', Tag::class, $this->getDefinitionIdentifier()),
                previous: $e
            );
        }

        if (false === $diRuntimeAttribute || null === $diRuntimeAttribute->tags) {
            yield from AttributeReader::getTagAttribute($this->classDefinitionReflection);

            return;
        }

        if ($diRuntimeAttribute->tags instanceof Tag) {
            yield $diRuntimeAttribute->tags;

            return;
        }

        foreach ($diRuntimeAttribute->tags as $argTag) {
            if ($argTag instanceof Tag) {
                yield $argTag;
            }
        }
    }

    /**
     * @throws AutowireAttributeException
     */
    private function getDiRuntimeAttributeConfiguringDefinition(ReflectionClass $class): DiRuntime|false
    {
        if (null === $this->classDefinition) {
            foreach (AttributeReader::getDiRuntimeAttribute($class) as $diRuntimeAttribute) {
                if ('' === $diRuntimeAttribute->containerIdentifier) {
                    return $diRuntimeAttribute;
                }
            }

            return false;
        }

        foreach (AttributeReader::getDiRuntimeAttribute($class) as $diRuntimeAttribute) {
            if ($this->containerIdentifier === $diRuntimeAttribute->containerIdentifier) {
                return $diRuntimeAttribute;
            }
        }

        return false;
    }
}

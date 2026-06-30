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
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterSetterInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionRuntimeInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedObjectDefinitionInterface;
use Kaspi\DiContainer\Interfaces\FreezeInterface;
use Kaspi\DiContainer\Interfaces\ResetInterface;
use Kaspi\DiContainer\Traits\TagsOnObjectDefinitionTrait;
use ReflectionClass;
use ReflectionException;

use function rtrim;
use function sprintf;
use function var_export;

final class DiDefinitionRuntime implements DiDefinitionRuntimeInterface, DiDefinitionTagArgumentInterface, DiTaggedObjectDefinitionInterface, ResetInterface, FreezeInterface, DiDefinitionResetterSetterInterface, DiDefinitionResetterInterface
{
    use TagsOnObjectDefinitionTrait {
        reset as private resetTrait;
    }

    private object $definition;

    private ReflectionClass $classDefinitionReflection;

    /**
     * @var callable(object): void|false|non-empty-string
     */
    private $resetter = false;

    /**
     * @param class-string|non-empty-string $containerIdentifierOrClass
     * @param null|class-string             $classDefinition
     */
    public function __construct(
        private readonly string $containerIdentifierOrClass,
        private readonly ?string $message = null,
        private readonly ?string $classDefinition = null,
    ) {}

    public function getIdentifier(): string
    {
        return $this->containerIdentifierOrClass;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): object
    {
        if (!isset($this->definition)) {
            $additionalMessage = $this->message ?? sprintf('You should replace the value of definition in the runtime container using the method DiContainerInterface::set(%s, $objectInstance).', var_export($this->containerIdentifierOrClass, true));

            throw new DiDefinitionException(
                rtrim(
                    sprintf('The runtime definition with container identifier %s cannot be resolved. %s', var_export($this->containerIdentifierOrClass, true), $additionalMessage)
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
                sprintf('You should to be defined a php class through the parameters $containerIdentifierOrClass or $classDefinition. Current values: $containerIdentifierOrClass %s, $classDefinition %s', var_export($this->containerIdentifierOrClass, true), var_export($this->classDefinition, true)),
                previous: $e,
            );
        }

        return $this->classDefinitionReflection->implementsInterface($interface);
    }

    public function getDefinitionIdentifier(): string
    {
        return $this->classDefinition ?? $this->containerIdentifierOrClass; // @phpstan-ignore return.type
    }

    public function reset(): void
    {
        $this->resetTrait();

        unset(
            $this->definition,
            $this->classDefinitionReflection,
        );
    }

    public function getResetter(): callable|false|string
    {
        return $this->resetter;
    }

    public function setResetter(callable|string $resetter): static
    {
        if ($this->isFrozen) {
            throw new DiDefinitionException(
                sprintf('Cannot call \%s::setResetter() on a frozen definition.', __CLASS__)
            );
        }

        $this->resetter = $resetter;

        return $this;
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
        $foundDiRuntimeAttribute = false;

        if (null === $this->classDefinition) {
            // We need to ensure that all attributes that have `DiRuntime::$containerIdentifier` are unique.
            foreach (AttributeReader::getDiRuntimeAttribute($class) as $diRuntimeAttribute) {
                if ('' === $diRuntimeAttribute->containerIdentifier) {
                    $foundDiRuntimeAttribute = $diRuntimeAttribute;
                }
            }

            return $foundDiRuntimeAttribute;
        }

        // We need to ensure that all attributes that have `DiRuntime::$containerIdentifier` are unique.
        foreach (AttributeReader::getDiRuntimeAttribute($class) as $diRuntimeAttribute) {
            if ($this->containerIdentifierOrClass === $diRuntimeAttribute->containerIdentifier) {
                $foundDiRuntimeAttribute = $diRuntimeAttribute;
            }
        }

        return $foundDiRuntimeAttribute;
    }
}

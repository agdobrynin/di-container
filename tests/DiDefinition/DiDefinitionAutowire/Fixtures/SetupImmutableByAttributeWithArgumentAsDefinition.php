<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs as DiTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue as DiValue;

final class SetupImmutableByAttributeWithArgumentAsDefinition
{
    private ?SomeClass $someClass = null;
    private ?string $anyAsContainerIdentifier = null;
    private ?string $anyAsEscapedString = null;

    private ?string $anyAsString = null;

    /**
     * @var iterable<RuleInterface>
     */
    private iterable $rules;

    /**
     * Will return SomeClass::SomeClass.
     */
    public function getSomeClass(): ?SomeClass
    {
        return $this->someClass;
    }

    /**
     * Will return value from the container mock object.
     */
    public function getAnyAsContainerIdentifier(): ?string
    {
        return $this->anyAsContainerIdentifier;
    }

    /**
     * Will return string "@la-la-la".
     */
    public function getAnyAsEscapedString(): ?string
    {
        return $this->anyAsEscapedString;
    }

    /**
     * Will return "any_string".
     */
    public function getAnyAsString(): ?string
    {
        return $this->anyAsString;
    }

    #[SetupImmutable(someClass: new DiGet('services.some_class'))]
    public function withSomeClassAsContainerIdentifier($someClass): self
    {
        $new = clone $this;
        $new->someClass = $someClass;

        return $new;
    }

    #[SetupImmutable(any: new DiGet('services.any_string'))]
    public function withAnyAsContainerIdentifier(string $any): self
    {
        $new = clone $this;
        $new->anyAsContainerIdentifier = $any;

        return $new;
    }

    #[SetupImmutable(any: new DiValue('@la-la-la'))]
    public function withAnyAsEscapedString(string $any): self
    {
        $new = clone $this;
        $new->anyAsEscapedString = $any;

        return $new;
    }

    #[SetupImmutable(anyAsString: 'any_string')]
    public function withAnyAsString(string $anyAsString): self
    {
        $new = clone $this;
        $new->anyAsString = $anyAsString;

        return $new;
    }

    #[SetupImmutable(new DiTaggedAs('tags.rule_interface'))]
    public function withRules(iterable $rules): self
    {
        $new = clone $this;
        $new->rules = $rules;

        return $new;
    }

    public function getRules(): iterable
    {
        return $this->rules;
    }
}

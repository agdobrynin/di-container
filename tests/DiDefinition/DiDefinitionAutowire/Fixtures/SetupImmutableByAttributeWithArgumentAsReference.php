<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\SetupImmutable;

final class SetupImmutableByAttributeWithArgumentAsReference
{
    private ?SomeClass $someClass = null;
    private ?string $anyAsContainerIdentifier = null;
    private ?string $anyAsEscapedString = null;

    private ?string $anyAsString = null;

    public function getSomeClass(): ?SomeClass
    {
        return $this->someClass;
    }

    public function getAnyAsContainerIdentifier(): ?string
    {
        return $this->anyAsContainerIdentifier;
    }

    public function getAnyAsEscapedString(): ?string
    {
        return $this->anyAsEscapedString;
    }

    public function getAnyAsString(): ?string
    {
        return $this->anyAsString;
    }

    #[SetupImmutable(someClass: '@services.some_class')]
    public function withSomeClassAsContainerIdentifier($someClass): self
    {
        $new = clone $this;
        $new->someClass = $someClass;

        return $new;
    }

    #[SetupImmutable(any: '@services.any_string')]
    public function withAnyAsContainerIdentifier(string $any): self
    {
        $new = clone $this;
        $new->anyAsContainerIdentifier = $any;

        return $new;
    }

    #[SetupImmutable(any: '@@services.any_string')]
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
}

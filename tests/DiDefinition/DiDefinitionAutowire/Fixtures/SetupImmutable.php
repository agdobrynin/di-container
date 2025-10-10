<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

final class SetupImmutable
{
    private ?SomeClass $someClass = null;

    public function __construct() {}

    public function getSomeClass(): ?SomeClass
    {
        return $this->someClass;
    }

    public function withSomeClassClonedReturnSelf(SomeClass $someClass): self
    {
        $new = clone $this;
        $new->someClass = $someClass;

        return $new;
    }

    public function withSomeClassClonedReturnSameClass(SomeClass $someClass): SetupImmutable
    {
        $new = clone $this;
        $new->someClass = $someClass;

        return $new;
    }

    public function withSomeClassClonedNotReturnTypehint(SomeClass $someClass)
    {
        $new = clone $this;
        $new->someClass = $someClass;

        return $new;
    }

    public function withSomeClassNotClonedReturnSelf(SomeClass $someClass): self
    {
        $this->someClass = $someClass;

        return $this;
    }

    public function withSomeClassFailReturnType(SomeClass $someClass): SomeClass
    {
        return $this->someClass = $someClass;
    }

    public function withSomeClassFailReturnObject(SomeClass $someClass)
    {
        return $this->someClass = $someClass;
    }

    public function withSomeClassFailReturnTypehintVoid(SomeClass $someClass): void
    {
        $this->someClass = $someClass;
    }
}

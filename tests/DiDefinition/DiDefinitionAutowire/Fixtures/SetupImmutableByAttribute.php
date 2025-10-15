<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\SetupImmutable;

final class SetupImmutableByAttribute
{
    private ?SomeClass $someClass = null;

    #[SetupImmutable]
    public function withSomeClass(?SomeClass $someClass): self
    {
        $new = clone $this;
        $new->someClass = $someClass;

        return $new;
    }

    public function getSomeClass(): ?SomeClass
    {
        return $this->someClass;
    }
}

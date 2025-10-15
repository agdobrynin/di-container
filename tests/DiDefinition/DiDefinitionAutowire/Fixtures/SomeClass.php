<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

final class SomeClass
{
    public function __construct(private ?string $value = null) {}

    public function getValue(): ?string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\CallableEntry\Fixtures;

final class Foo
{
    public function __construct(private readonly string $name) {}

    public function getName(): string
    {
        return $this->name;
    }
}

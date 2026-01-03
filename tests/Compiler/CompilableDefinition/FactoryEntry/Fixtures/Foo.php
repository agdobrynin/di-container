<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\FactoryEntry\Fixtures;

final class Foo
{
    public function __construct(private string $token) {}
}

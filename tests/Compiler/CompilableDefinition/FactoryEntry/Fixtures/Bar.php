<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\FactoryEntry\Fixtures;

final class Bar
{
    public function __construct(public readonly string $str) {}
}

<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry\FixturesForCompile;

final class Bar
{
    private string $bar = 'Lorem ipsum bar';

    public function getBar(): string
    {
        return $this->bar;
    }
}

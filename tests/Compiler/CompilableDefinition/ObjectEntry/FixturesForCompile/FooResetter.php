<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\ObjectEntry\FixturesForCompile;

final class FooResetter
{
    public static function doReset(Foo $foo): void
    {
        $foo->doResetManually();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\CallableEntry\Fixtures;

final class Bar
{
    public static function baz(Foo $foo): string
    {
        return 'baz-'.$foo->getName();
    }
}

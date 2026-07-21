<?php

declare(strict_types=1);

namespace Tests\ObjectResetters\Fixtures;

final class FooResetter
{
    public static function doReset(Foo $foo): void
    {
        $foo->foo = 'foo';
    }
}

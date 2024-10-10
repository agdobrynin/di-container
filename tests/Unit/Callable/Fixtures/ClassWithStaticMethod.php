<?php

declare(strict_types=1);

namespace Tests\Unit\Callable\Fixtures;

class ClassWithStaticMethod
{
    public static function foo(): string
    {
        return 'I am foo static';
    }
}

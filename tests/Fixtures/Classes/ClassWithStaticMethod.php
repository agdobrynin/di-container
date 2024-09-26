<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class ClassWithStaticMethod
{
    public static function talk(): string
    {
        return 'Hello!';
    }
}

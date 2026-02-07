<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryCompile\Fixtures;

final class FooFactoryStatic
{
    public static function create(string $newStr): Foo
    {
        return new Foo($newStr);
    }
}

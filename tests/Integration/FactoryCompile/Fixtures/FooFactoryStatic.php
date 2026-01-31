<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryCompile\Fixtures;

final class FooFactoryStatic
{
    public static function create(): Foo
    {
        return new Foo('Lorem ipsum');
    }
}

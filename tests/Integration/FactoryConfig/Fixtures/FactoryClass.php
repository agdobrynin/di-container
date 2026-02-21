<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

final class FactoryClass
{
    public static function create(): Foo
    {
        return new Foo('Lorem ipsum one');
        // дополнительные настройки объекта
    }
}

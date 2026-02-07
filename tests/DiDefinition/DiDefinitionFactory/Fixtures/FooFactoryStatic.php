<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

final class FooFactoryStatic
{
    public static function make(mixed $bar): mixed
    {
        return $bar;
    }
}

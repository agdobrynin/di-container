<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

final class Baz
{
    public static function create(string $str = 'qux'): string
    {
        return $str;
    }
}

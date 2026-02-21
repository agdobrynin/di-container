<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

use function sprintf;

final class FactoryClassArgs
{
    public static function create(string $var1, string $var2, Bar $bar): Foo
    {
        $str = sprintf('%s | %s | %s', $var1, $var2, $bar->str);

        return new Foo($str);
    }
}

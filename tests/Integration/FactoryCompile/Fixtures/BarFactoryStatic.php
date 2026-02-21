<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryCompile\Fixtures;

final class BarFactoryStatic
{
    public static function create(Baz $baz): Bar
    {
        return new Bar($baz);
    }
}

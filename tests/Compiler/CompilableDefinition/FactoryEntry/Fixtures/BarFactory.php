<?php

declare(strict_types=1);

namespace Tests\Compiler\CompilableDefinition\FactoryEntry\Fixtures;

final class BarFactory
{
    public function __construct(private Bar $bar) {}

    public static function create(string $str): Bar
    {
        return new Bar($str);
    }
}

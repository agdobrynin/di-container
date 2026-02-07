<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory\Fixtures;

final class Bar
{
    public function __construct(public readonly string $str) {}
}

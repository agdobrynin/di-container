<?php

declare(strict_types=1);

namespace Tests\Integration\ResolveParameterWithDefaultValue\Fixtures;

final class Foo
{
    public function __construct(public readonly ?BarInterface $bar = null) {}
}

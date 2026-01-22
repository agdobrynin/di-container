<?php

declare(strict_types=1);

namespace Tests\Integration\ResolveParameterWithDefaultValue\Fixtures;

use ArrayIterator;

final class Bar
{
    public function __construct(public readonly ArrayIterator $bar = new ArrayIterator(['foo', 'bar'])) {}
}

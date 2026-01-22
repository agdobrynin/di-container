<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory\Fixtures;

final class ParameterByDiFactory
{
    public function __construct(
        public readonly MyClass $dependency
    ) {}
}

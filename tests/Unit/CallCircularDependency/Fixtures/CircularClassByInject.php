<?php

declare(strict_types=1);

namespace Tests\Unit\CallCircularDependency\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class CircularClassByInject
{
    public function __construct(#[InjectContext] CircularClassByInterfaceInject $circular) {}
}

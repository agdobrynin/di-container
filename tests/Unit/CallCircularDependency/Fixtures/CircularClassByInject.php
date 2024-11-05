<?php

declare(strict_types=1);

namespace Tests\Unit\CallCircularDependency\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class CircularClassByInject
{
    public function __construct(#[Inject] CircularClassByInterfaceInject $circular) {}
}

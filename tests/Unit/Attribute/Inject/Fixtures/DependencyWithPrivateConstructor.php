<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

class DependencyWithPrivateConstructor
{
    private function __construct() {}
}

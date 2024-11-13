<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

class InjectDependencyWithPrivateConstructor
{
    public function __construct(public DependencyWithPrivateConstructor $withPrivateConstructor) {}
}

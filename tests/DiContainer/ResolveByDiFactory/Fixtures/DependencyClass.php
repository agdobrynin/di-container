<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory\Fixtures;

class DependencyClass
{
    public function __construct(public ?string $dependency = null) {}
}

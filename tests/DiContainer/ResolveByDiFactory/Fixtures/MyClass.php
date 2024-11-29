<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(MyClassDiFactory::class)]
class MyClass
{
    public function __construct(public DependencyClass $dependency) {}
}

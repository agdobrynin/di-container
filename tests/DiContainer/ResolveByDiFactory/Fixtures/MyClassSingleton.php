<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(MyClassSingletonDiFactory::class, isSingleton: true)]
class MyClassSingleton
{
    public function __construct(public DependencyClass $dependency) {}
}

<?php

declare(strict_types=1);

namespace Tests\Unit\AttributeDiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(ClassADiFactory::class)]
class ClassA
{
    public function __construct(public ClassDependency $dependency) {}
}

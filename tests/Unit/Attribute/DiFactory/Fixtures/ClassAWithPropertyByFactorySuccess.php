<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

class ClassAWithPropertyByFactorySuccess
{
    public function __construct(
        #[DiFactory(ClassDependencyOnPropertyDiFactory::class)]
        public ClassDependency $dependency
    ) {}
}
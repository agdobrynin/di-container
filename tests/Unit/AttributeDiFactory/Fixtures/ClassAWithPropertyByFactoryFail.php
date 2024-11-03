<?php

declare(strict_types=1);

namespace Tests\Unit\AttributeDiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

class ClassAWithPropertyByFactoryFail
{
    public function __construct(
        #[DiFactory(ClassDependencyOnPropertyDiFactory::class)]
        #[DiFactory(ClassDependencyOnPropertyDiFactory::class)]
        public ClassDependency $dependency
    ) {}
}

<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\InjectContext;
use Tests\Unit\Container\ContainerWithUnionTypeOrEmptyTypeParametersTest;

class ClassWithUnionType
{
    public function __construct(
        #[InjectContext(
            \ReflectionMethod::class,
            arguments: [
                'objectOrMethod' => ContainerWithUnionTypeOrEmptyTypeParametersTest::class,
                'method' => 'testUnionTypeByAttribute',
            ]
        )]
        public \ReflectionClass|\ReflectionMethod $dependency
    ) {}
}

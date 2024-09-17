<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\DiFactory;

class ClassWithFactoryArgument
{
    public function __construct(
        #[DiFactory(FactoryClassWithDiFactoryArgument::class)]
        public \ArrayIterator $arrayObject
    ) {}
}

<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Factory;

class ClassWithFactoryArgument
{
    public function __construct(
        #[Factory(FactoryClassWithFactoryArgument::class)]
        public \ArrayIterator $arrayObject
    ) {}
}

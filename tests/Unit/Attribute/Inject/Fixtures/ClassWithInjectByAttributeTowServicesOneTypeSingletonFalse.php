<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class ClassWithInjectByAttributeTowServicesOneTypeSingletonFalse
{
    public function __construct(
        #[InjectContext(arguments: ['array' => ['one', 'two']], isSingleton: false)]
        public \ArrayIterator $iterator1,
        #[InjectContext(arguments: ['array' => ['three', 'four']], isSingleton: false)]
        public \ArrayIterator $iterator2,
    ) {}
}

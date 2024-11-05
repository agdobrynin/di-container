<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class ClassWithInjectByAttributeTowServicesOneTypeSingletonFalse
{
    public function __construct(
        #[Inject(arguments: ['array' => ['one', 'two']], isSingleton: false)]
        public \ArrayIterator $iterator1,
        #[Inject(arguments: ['array' => ['three', 'four']], isSingleton: false)]
        public \ArrayIterator $iterator2,
    ) {}
}

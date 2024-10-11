<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class ClassWithInjectByAttributeTowServicesOneType
{
    public function __construct(
        #[Inject(arguments: ['array' => ['one', 'tow']])]
        public \ArrayIterator $iterator1,
        #[Inject(arguments: ['array' => ['three', 'four']])]
        public \ArrayIterator $iterator2,
    ) {}
}

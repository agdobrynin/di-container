<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class TowClassesWithInjectA
{
    public function __construct(
        #[Inject(arguments: ['array' => ['one', 'two']])]
        public \ArrayIterator $iterator
    ) {}
}

class TowClassesWithInjectB
{
    public function __construct(#[Inject(arguments: ['array' => ['tree', 'four']])] public \ArrayIterator $iterator) {}
}

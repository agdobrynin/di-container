<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\InjectContext;

class InjectFailType
{
    public function __construct(
        #[InjectContext(id: \ArrayIterator::class)]
        public \SplQueue $queue,
    ) {}
}

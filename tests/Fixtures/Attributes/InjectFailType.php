<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class InjectFailType
{
    public function __construct(
        #[Inject(id: \ArrayIterator::class)]
        public \SplQueue $queue,
    ) {}
}

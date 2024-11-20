<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\InjectContext;

class InjectSplClass
{
    public function __construct(
        #[InjectContext]
        public \SplQueue $queue,
    ) {}
}

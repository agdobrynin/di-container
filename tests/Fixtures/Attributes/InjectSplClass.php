<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class InjectSplClass
{
    public function __construct(
        #[Inject]
        public \SplQueue $queue,
    ) {}
}

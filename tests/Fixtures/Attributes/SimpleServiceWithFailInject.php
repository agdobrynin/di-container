<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\InjectContext;

class SimpleServiceWithFailInject
{
    public function __construct(
        #[InjectContext]
        public FreeInterface $service1,
    ) {}
}

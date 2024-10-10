<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class SimpleServiceWithFailInject
{
    public function __construct(
        #[Inject]
        public FreeInterface $service1,
    ) {}
}

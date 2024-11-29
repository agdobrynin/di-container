<?php

declare(strict_types=1);

namespace Tests\DiContainer\Exceptions\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class FirstClass
{
    public function __construct(
        #[Inject('services.second')]
        public SecondClass $service
    ) {}
}

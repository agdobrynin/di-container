<?php

declare(strict_types=1);

namespace Tests\DiContainer\Exceptions\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class ThirdClass
{
    public function __construct(
        #[Inject]
        public FirstClass $service
    ) {}
}

<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\CircularReference\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class ThirdClass
{
    public function __construct(
        #[Inject]
        public FirstClass $service
    ) {}
}

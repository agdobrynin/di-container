<?php

declare(strict_types=1);

namespace Tests\DiContainer\Exceptions\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class SecondClass
{
    public function __construct(
        #[Inject('services.third')]
        public ThirdClass $service
    ) {}
}

<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\CircularReference\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class FirstClass
{
    public function __construct(
        #[Inject('services.second')]
        public SecondClass $service
    ) {}

    public function __invoke(): void {}
}

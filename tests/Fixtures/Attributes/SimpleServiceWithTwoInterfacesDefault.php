<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class SimpleServiceWithTwoInterfacesDefault
{
    public function __construct(
        #[Inject]
        public SimpleInterfaceSharedDefault $service1,
        #[Inject]
        public SimpleInterfaceSharedDefault $service2,
    ) {}
}

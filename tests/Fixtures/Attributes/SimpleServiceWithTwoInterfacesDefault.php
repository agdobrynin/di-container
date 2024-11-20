<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\InjectContext;

class SimpleServiceWithTwoInterfacesDefault
{
    public function __construct(
        #[InjectContext]
        public SimpleInterfaceSharedDefault $service1,
        #[InjectContext]
        public SimpleInterfaceSharedDefault $service2,
    ) {}
}

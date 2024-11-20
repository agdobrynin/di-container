<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class FreeInterfaceByInjectClass
{
    public function __construct(
        #[InjectContext]
        public FreeInterfaceByInject $interface
    ) {}
}

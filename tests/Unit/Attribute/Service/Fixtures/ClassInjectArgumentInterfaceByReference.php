<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class ClassInjectArgumentInterfaceByReference
{
    public function __construct(
        #[InjectContext]
        public ByReferenceInterface $service
    ) {}
}

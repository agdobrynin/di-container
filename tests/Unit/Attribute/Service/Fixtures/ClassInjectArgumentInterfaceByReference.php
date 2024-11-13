<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class ClassInjectArgumentInterfaceByReference
{
    public function __construct(
        #[Inject]
        public ByReferenceInterface $service
    ) {}
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class ClassInjectArgumentInterfaceByReferenceNotFound
{
    public function __construct(
        #[InjectContext]
        public ByReferenceNotFoundInterface $service
    ) {}
}

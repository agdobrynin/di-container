<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class ClassInjectArgumentInterfaceByReferenceSpaceInIdentifier
{
    public function __construct(
        #[InjectContext]
        public ByReferenceSpaceInIdentifierInterface $service
    ) {}
}

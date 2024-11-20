<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class ClassInjectArgumentInterfaceByReferenceEmptyIdentifier
{
    public function __construct(
        #[InjectContext]
        public ByReferenceWithoutIdentifierInterface $service
    ) {}
}

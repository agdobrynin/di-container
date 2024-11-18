<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class ClassInjectArgumentInterfaceByReferenceEmptyIdentifier
{
    public function __construct(
        #[Inject]
        public ByReferenceWithoutIdentifierInterface $service
    ) {}
}

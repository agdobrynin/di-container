<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class ClassInjectArgumentInterfaceByReferenceSpaceInIdentifier
{
    public function __construct(
        #[Inject]
        public ByReferenceSpaceInIdentifierInterface $service
    ) {}
}

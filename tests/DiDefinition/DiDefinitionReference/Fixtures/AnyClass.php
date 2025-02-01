<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionReference\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class AnyClass
{
    public function __construct(
        #[Inject(AnyTwoService::class)]
        public AnyInterface $any
    ) {}
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class InjectArgumentByArgumentName
{
    public function __construct(
        #[Inject]
        public array $argName
    ) {}
}

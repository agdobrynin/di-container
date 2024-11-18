<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByReference;

class InjectArgumentByArgumentName
{
    public function __construct(
        #[InjectByReference('public.welcome_array')]
        public array $argName
    ) {}
}

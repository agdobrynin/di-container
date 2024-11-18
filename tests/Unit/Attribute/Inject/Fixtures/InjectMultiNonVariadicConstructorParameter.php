<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class InjectMultiNonVariadicConstructorParameter
{
    public function __construct(
        #[Inject]
        #[Inject]
        public array $param1
    ) {}
}

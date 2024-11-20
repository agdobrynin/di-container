<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject\Fixtures;

use Kaspi\DiContainer\Attributes\InjectContext;

class InjectMultiNonVariadicConstructorParameter
{
    public function __construct(
        #[InjectContext]
        #[InjectContext]
        public array $param1
    ) {}
}

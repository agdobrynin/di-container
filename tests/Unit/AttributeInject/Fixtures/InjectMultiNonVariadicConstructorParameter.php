<?php

declare(strict_types=1);

namespace Tests\Unit\AttributeInject\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class InjectMultiNonVariadicConstructorParameter
{
    public function __construct(
        #[Inject('@param1')]
        #[Inject('@param2')]
        public array $param1
    ) {}
}

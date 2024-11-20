<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\InjectByReference;

class InjectByReferenceTwiceNonVariadicArgument
{
    public function __construct(
        #[InjectByReference('inject1')]
        #[InjectByReference('inject2')]
        public \ArrayIterator $iterator
    ) {}
}

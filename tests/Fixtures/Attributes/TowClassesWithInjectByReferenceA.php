<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class TowClassesWithInjectByReferenceA
{
    public function __construct(
        #[Inject('@inject1')]
        public \ArrayIterator $iterator
    ) {}
}

class TowClassesWithInjectByReferenceB
{
    public function __construct(
        #[Inject('@inject2')]
        public \ArrayIterator $iterator
    ) {}
}

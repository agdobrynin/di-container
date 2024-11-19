<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByReference;

class TowClassesWithInjectByReferenceA
{
    public function __construct(
        #[InjectByReference('inject1')]
        public \ArrayIterator $iterator
    ) {}
}

class TowClassesWithInjectByReferenceB
{
    public function __construct(
        #[InjectByReference('inject2')]
        public \ArrayIterator $iterator
    ) {}
}

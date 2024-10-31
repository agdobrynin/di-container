<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class ClassD
{
    public function __construct(
        #[Inject(ClassB::class)]
        public ClassA|ClassB $var
    ) {}
}

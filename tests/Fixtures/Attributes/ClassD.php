<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\InjectContext;

class ClassD
{
    public function __construct(
        #[InjectContext(ClassB::class)]
        public ClassA|ClassB $var
    ) {}
}

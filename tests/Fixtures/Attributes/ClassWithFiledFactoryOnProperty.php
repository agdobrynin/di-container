<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\DiFactory;

class ClassWithFiledFactoryOnProperty
{
    public function make(
        #[DiFactory(Lorem::class)]
        Lorem $lorem
    ): void {}
}

<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Factory;

class ClassWithFiledFactoryOnProperty
{
    public function make(
        #[Factory(Lorem::class)]
        Lorem $lorem
    ): void {}
}

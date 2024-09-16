<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Factory;

#[Factory(FactorySuperClass::class)]
class SuperClass
{
    public function __construct(public string $name, public int $age) {}
}

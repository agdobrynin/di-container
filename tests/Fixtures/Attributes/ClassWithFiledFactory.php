<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Factory;

#[Factory(Lorem::class)]
class ClassWithFiledFactory
{
    public function __construct(public string $name, public int $age) {}
}
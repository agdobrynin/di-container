<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory([FactoryClass::class, 'create'])]
final class FooAttrOne
{
    public function __construct(public readonly string $str) {}
}

<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(FactoryInvokableClass::class)]
final class FooAttrInvokable
{
    public function __construct(public readonly string $str) {}
}

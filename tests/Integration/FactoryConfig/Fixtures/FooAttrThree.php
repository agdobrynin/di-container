<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(['factories.factory_none_static_class', 'create'])]
final class FooAttrThree
{
    public function __construct(public readonly string $str) {}
}

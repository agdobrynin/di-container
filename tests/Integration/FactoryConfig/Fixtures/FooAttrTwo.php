<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory([FactoryNoneStaticClass::class, 'create'])]
final class FooAttrTwo
{
    public function __construct(public readonly string $str) {}
}

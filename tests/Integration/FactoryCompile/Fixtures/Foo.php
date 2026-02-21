<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryCompile\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(
    [FooFactoryStatic::class, 'create'],
    arguments: ['newStr' => 'Lorem ipsum dolor sit amet']
)]
final class Foo
{
    public function __construct(public string $strFoo) {}
}

<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryConfig\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(
    [FactoryClassArgs::class, 'create'],
    arguments: [
        'value 2',
        'var2' => 'value 3',
    ],
)]
final class FooAttrArgs
{
    public function __construct(public readonly string $str) {}
}

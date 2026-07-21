<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Attributes\Tag;

#[Autowire]
#[Autowire(id: 'foo', tags: [], setups: [])]
#[Autowire(
    id: 'bar',
    tags: new Tag('tags.bar'),
    setups: [
        'doSetupTwo' => new Setup(),
    ],
)]
#[Autowire(
    id: 'baz',
    tags: [
        new Tag('tags.baz'),
        new Tag('tags.qux'),
    ],
    setups: [
        'doSetupImmutable' => new SetupImmutable('bar'),
        'doSetupThree' => [
            new Autowire('any.class'), // The element will be skipped
            new Setup('foo'),
        ],
    ]
)]
#[Autowire(id: 'qux', setups: [
    'noneExist' => new Setup(),
])]
#[Tag('tags.foo')]
final class FooMultiConfigSetup
{
    #[Setup]
    public function doSetup(): void {}

    public function doSetupTwo(): void {}

    public function doSetupThree(): void {}

    public function doSetupImmutable(): self {}
}

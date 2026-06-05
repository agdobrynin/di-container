<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;

#[Autowire]
#[Autowire(id: 'foo', setups: [])]
#[Autowire(id: 'bar', setups: [
    'doSetupTwo' => new Setup(),
])]
#[Autowire(id: 'baz', setups: [
    'doSetupImmutable' => new SetupImmutable('bar'),
    'doSetupThree' => [
        new Autowire('any.class'), // The element will be skipped
        new Setup('foo'),
    ],
])]
#[Autowire(id: 'qux', setups: [
    'noneExist' => new Setup(),
])]
final class FooMultiConfigSetup
{
    #[Setup]
    public function doSetup(): void {}

    public function doSetupTwo(): void {}

    public function doSetupThree(): void {}

    public function doSetupImmutable(): self {}
}

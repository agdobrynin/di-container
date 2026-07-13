<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\AttributeResetterConfig;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(resetter: [FooResetter::class, 'reset'])]
final class Foo {}

final class FooResetter
{
    public static function reset(Foo $foo): void {}
}

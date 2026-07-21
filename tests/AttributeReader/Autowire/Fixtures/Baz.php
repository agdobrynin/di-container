<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(resetter: [BazResetter::class, 'doReset'])]
final class Baz {}

final class BazResetter
{
    public static function doReset(Baz $baz): void {}
}

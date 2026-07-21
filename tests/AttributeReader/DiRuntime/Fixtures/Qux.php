<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiRuntime\Fixtures;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime(resetter: [QuxResetter::class, 'doReset'])]
final class Qux {}

final class QuxResetter
{
    public static function doReset(): void {}
}

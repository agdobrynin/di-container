<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByAutowireAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isSingleton: true, resetter: [ThreeResetter::class, 'flush'])]
final class Three {}

final class ThreeResetter
{
    public static function flush(Three $three): void {}
}

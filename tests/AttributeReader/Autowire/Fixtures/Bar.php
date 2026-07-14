<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(resetter: 'doReset')]
final class Bar
{
    public function doReset(): void {}
}

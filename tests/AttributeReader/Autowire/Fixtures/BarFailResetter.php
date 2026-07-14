<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(resetter: [NoneExist::class, 'method'])]
final class BarFailResetter {}

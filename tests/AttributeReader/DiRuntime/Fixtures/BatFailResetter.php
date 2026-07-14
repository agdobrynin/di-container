<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiRuntime\Fixtures;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime(resetter: [NonExist::class, 'method'])]
final class BatFailResetter {}

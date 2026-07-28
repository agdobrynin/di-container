<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isLazy: true)]
final class FooLazy {}

<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\DiRuntime;

#[Autowire]
#[DiRuntime]
final class Foo {}

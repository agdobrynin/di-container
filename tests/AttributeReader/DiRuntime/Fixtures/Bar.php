<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiRuntime\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime]
#[Autowire]
final class Bar {}

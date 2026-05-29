<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiRuntime\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime]
#[DiFactory('\log')]
final class Baz {}

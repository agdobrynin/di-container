<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiFactory(FooFactoryOne::class)]
#[DiRuntime]
final class Foo {}

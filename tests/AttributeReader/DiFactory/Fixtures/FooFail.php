<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(FooFactoryOne::class)]
#[DiFactory(FooFactoryTwo::class)]
final class FooFail {}

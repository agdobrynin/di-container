<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(MainFirstDiFactory::class)]
class Main {}

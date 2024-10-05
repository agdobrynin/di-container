<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(id: FlyClassByDiFactory::class, isShared: true)]
class FlyClass {}

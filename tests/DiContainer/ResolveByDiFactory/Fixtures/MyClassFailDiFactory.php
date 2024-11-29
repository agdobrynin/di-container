<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory('service.one')]
class MyClassFailDiFactory {}

<?php

declare(strict_types=1);

namespace Tests\DiContainer\GetDefinition\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(id: 'service.one')]
#[Autowire(id: 'service.one')]
final class Qux {}

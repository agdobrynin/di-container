<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByAutowireAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(isSingleton: true)]
final class One {}

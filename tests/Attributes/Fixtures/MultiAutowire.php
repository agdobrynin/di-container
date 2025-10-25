<?php

declare(strict_types=1);

namespace Tests\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire('service.singleton', isSingleton: true)]
#[Autowire(isSingleton: true)]
final class MultiAutowire {}

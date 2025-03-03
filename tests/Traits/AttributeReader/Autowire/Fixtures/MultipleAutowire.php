<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire]
#[Autowire('service.singleton', isSingleton: true)]
#[Autowire('service.none_singleton')]
final class MultipleAutowire {}

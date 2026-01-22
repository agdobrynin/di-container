<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire]
#[Autowire('service.singleton', isSingleton: true)]
#[Autowire('service.none_singleton', isSingleton: false)]
final class MultipleAutowire {}

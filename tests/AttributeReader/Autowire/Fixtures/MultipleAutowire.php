<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(arguments: ['foo'])]
#[Autowire('service.singleton', isSingleton: true, arguments: ['bar'])]
#[Autowire('service.none_singleton', isSingleton: false, arguments: ['baz'])]
final class MultipleAutowire {}

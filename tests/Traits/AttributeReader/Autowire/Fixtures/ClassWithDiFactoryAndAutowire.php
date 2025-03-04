<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\DiFactory;

#[Autowire]
#[DiFactory('someFactory::method')]
final class ClassWithDiFactoryAndAutowire {}

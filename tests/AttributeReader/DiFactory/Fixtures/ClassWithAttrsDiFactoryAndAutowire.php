<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory('someFactory::method')]
#[Autowire]
final class ClassWithAttrsDiFactoryAndAutowire {}

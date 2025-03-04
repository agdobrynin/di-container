<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\AutowireExclude;
use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory('someFactory::method')]
#[AutowireExclude]
final class ClassWithAttrsDiFactoryAndAutowireExclude {}

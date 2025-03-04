<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\AutowireExclude;

#[AutowireExclude]
#[Autowire]
final class ClassWithAttrsAutowireExcludeAndAutowire {}

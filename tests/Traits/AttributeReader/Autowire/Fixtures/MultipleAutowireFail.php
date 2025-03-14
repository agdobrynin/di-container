<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Autowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire('service', isSingleton: true)]
#[Autowire('service')]
final class MultipleAutowireFail {}

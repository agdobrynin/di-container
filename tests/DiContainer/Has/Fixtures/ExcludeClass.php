<?php

declare(strict_types=1);

namespace Tests\DiContainer\Has\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\AutowireExclude;

#[Autowire] // always ignore because use attribute AutowireExclude
#[AutowireExclude]
final class ExcludeClass {}

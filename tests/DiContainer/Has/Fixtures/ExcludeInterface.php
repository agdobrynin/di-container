<?php

declare(strict_types=1);

namespace Tests\DiContainer\Has\Fixtures;

use Kaspi\DiContainer\Attributes\AutowireExclude;

#[AutowireExclude]
interface ExcludeInterface {}

<?php

declare(strict_types=1);

namespace Tests\Integration\ArgumentsViaAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service(Foo::class)]
interface QuxInterface {}

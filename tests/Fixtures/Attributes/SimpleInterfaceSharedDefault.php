<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Service;

#[Service(SimpleServiceSharedDefault::class)]
interface SimpleInterfaceSharedDefault {}

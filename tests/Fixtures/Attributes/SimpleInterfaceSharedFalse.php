<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Service;

#[Service(
    id: SimpleServiceSharedFalse::class,
    isSingleton: false
)]
interface SimpleInterfaceSharedFalse {}

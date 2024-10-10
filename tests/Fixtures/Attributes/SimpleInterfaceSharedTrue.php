<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Service;

#[Service(
    id: SimpleServiceSharedTrue::class,
    isSingleton: true
)]
interface SimpleInterfaceSharedTrue {}

<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByInterface\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service(ServiceViaAttributeWithClassA::class)]
interface ServiceViaAttributeWithClassInterface {}

<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByInterface\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service('services.class-a', isSingleton: true)]
interface ServiceViaAttributeWithReferenceInterface {}

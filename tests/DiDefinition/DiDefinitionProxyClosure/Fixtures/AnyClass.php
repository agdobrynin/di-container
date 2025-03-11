<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionProxyClosure\Fixtures;

use Closure;
use Kaspi\DiContainer\Attributes\ProxyClosure;

class AnyClass
{
    public function __construct(
        #[ProxyClosure(Tow::class)]
        public Closure $service
    ) {}
}

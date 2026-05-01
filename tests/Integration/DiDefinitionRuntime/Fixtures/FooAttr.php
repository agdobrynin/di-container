<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

final class FooAttr
{
    public function __construct(
        public readonly Bar $bar,
        #[Inject('service.foo')]
        public readonly object $service
    ) {}
}

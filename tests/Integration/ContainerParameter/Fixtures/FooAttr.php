<?php

declare(strict_types=1);

namespace Tests\Integration\ContainerParameter\Fixtures;

use Kaspi\DiContainer\Attributes\Parameter;

class FooAttr
{
    public function __construct(
        public readonly Bar $bar,
        #[Parameter]
        public readonly string $endpoint
    ) {}
}

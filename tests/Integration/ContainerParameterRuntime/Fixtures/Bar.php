<?php

declare(strict_types=1);

namespace Tests\Integration\ContainerParameterRuntime\Fixtures;

use Kaspi\DiContainer\Attributes\ParameterRuntime;

class Bar
{
    public function __construct(
        #[ParameterRuntime]
        public string $bat,
        #[ParameterRuntime('qux.value')]
        public int $qux
    ) {}
}

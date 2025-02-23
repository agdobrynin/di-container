<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByCallable;

final class ServiceThree
{
    public function __construct(
        #[InjectByCallable(MakeServiceTwo::class)]
        public Two $two,
    ) {}
}

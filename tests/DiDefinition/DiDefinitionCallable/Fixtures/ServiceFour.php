<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByCallable;

final class ServiceFour
{
    public function __construct(
        #[InjectByCallable('noneCallableString')]
        public Two $two,
    ) {}
}

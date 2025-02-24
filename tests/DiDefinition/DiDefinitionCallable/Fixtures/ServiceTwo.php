<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByCallable;

final class ServiceTwo
{
    public function __construct(
        #[InjectByCallable('Tests\Fixtures\funcServiceTwo')]
        public Two $two,
    ) {}
}

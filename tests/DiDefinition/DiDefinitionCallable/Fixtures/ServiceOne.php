<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable\Fixtures;

use Kaspi\DiContainer\Attributes\InjectByCallable;

final class ServiceOne
{
    public function __construct(
        #[InjectByCallable(Two::class.'::makeFromStatic')]
        public Two $two,
    ) {}
}

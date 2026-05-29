<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime\Fixtures2;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Qux
{
    public function __construct(
        #[TaggedAs(FooInterface::class)]
        public readonly iterable $tagged
    ) {}
}

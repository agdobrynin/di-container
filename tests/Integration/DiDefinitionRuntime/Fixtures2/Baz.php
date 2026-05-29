<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime\Fixtures2;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Baz
{
    public function __construct(
        #[TaggedAs('foo.attr', priorityDefaultMethod: 'getPriority', keyDefaultMethod: 'getKey')]
        public readonly iterable $tagged
    ) {}
}

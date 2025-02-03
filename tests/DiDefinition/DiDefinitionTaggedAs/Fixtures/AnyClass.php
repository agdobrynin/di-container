<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures;

use Kaspi\DiContainer\Attributes\TaggedAs;

class AnyClass
{
    public function __construct(
        #[TaggedAs('tags.handler-attribute')]
        public iterable $tagged
    ) {}
}

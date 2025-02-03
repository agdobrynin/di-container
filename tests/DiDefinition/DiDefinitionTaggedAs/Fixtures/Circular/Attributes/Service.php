<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\Attributes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class Service
{
    public function __construct(
        #[TaggedAs('tags.service-item')]
        public iterable $services
    ) {}
}

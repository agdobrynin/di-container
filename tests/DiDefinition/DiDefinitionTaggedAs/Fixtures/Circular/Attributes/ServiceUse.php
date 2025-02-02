<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\Attributes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ServiceUse
{
    public function __construct(
        #[TaggedAs(ServiceUseInterface::class)]
        public iterable $services
    ) {}
}

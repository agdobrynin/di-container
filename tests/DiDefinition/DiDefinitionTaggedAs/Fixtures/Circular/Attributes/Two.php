<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Circular\Attributes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.service-item')]
class Two
{
    public function __construct(public One $one) {}
}

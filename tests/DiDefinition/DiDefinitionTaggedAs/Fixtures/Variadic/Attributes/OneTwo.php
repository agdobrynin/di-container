<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\Attributes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.one')]
class OneTwo implements OneInterface {}

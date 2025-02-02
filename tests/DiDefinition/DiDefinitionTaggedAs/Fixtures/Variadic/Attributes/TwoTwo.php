<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Variadic\Attributes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tow', ['priority' => 100])]
class TwoTwo implements TwoInterface {}

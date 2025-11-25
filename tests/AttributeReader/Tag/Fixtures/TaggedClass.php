<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Tag\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.handler-one')]
#[Tag('tags.handler-two', ['priority' => 100], priority: 150)]
class TaggedClass {}

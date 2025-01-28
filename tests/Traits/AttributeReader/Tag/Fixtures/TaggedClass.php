<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Tag\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.handler-one')]
#[Tag('tags.handler-two', ['priority' => 100])]
class TaggedClass {}

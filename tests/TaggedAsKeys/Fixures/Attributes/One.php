<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixures\Attributes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.one', options: ['key.override' => 'key-service'], priority: 100)]
final class One {}

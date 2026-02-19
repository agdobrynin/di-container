<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\TaggedAttr;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.class')]
#[Tag('tags.one')]
final class Baz {}

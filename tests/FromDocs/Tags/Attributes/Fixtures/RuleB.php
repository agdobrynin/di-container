<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.rules-other', options: ['priority' => 100])]
class RuleB implements RuleInterface {}

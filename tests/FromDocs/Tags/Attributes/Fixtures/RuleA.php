<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.rules', priority: 10)]
#[Tag(name: 'tags.rules.priorityMethod', priorityMethod: 'getPriority')]
class RuleA implements RuleInterface
{
    public static function getPriority(): string
    {
        return 'AAA';
    }
}

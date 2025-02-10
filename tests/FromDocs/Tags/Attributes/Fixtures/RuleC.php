<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.rules', options: ['priority' => 100])]
#[Tag(name: 'tags.rules.priorityMethod')]
class RuleC
{
    public static function getCollectionPriority(): string
    {
        return 'BBB';
    }
}

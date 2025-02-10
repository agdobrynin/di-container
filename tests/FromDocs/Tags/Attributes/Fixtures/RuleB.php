<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.rules-other', options: ['priority' => 100])]
#[Tag(name: 'tags.rules.priorityMethod', priorityMethod: 'getPriorityOther')]
class RuleB implements RuleInterface
{
    public static function getPriorityOther(): string
    {
        return 'ZZZ';
    }
}

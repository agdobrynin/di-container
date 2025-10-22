<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.priority_vs_method_priority', priority: 10, priorityMethod: 'getPriority')]
final class TaggedPriorityVsMethodPriority
{
    public static function getPriority(): string
    {
        return 'group:10:0001';
    }

    public static function getPriorityByPhpDefinition(): string
    {
        return 'group:20:2000';
    }
}

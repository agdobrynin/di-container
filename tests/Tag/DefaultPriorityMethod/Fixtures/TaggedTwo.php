<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.tag-a', options: [], priority: 3, priorityTagMethod: 'getPriority')]
final class TaggedTwo implements TaggedInterface
{
    public static function getPriority(): int
    {
        return 100;
    }

    public static function getTaggedInterfacePriority(): string
    {
        return 'group1:101';
    }
}

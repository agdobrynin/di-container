<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.one', options: [], priorityTagMethod: 'getPriority')]
final class TaggedOne implements TaggedInterface
{
    public static function getPriority(): int
    {
        return 10;
    }

    public static function getCollectionPriority(): int
    {
        return 9;
    }
}

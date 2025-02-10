<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.one', options: [], priorityMethod: 'getPriority')]
final class TaggedOne implements TaggedInterface
{
    public static function getPriority(): int
    {
        return 10;
    }

    public static function getTaggedInterfacePriority(): string
    {
        return 'group1:1';
    }

    public static function getOneOfPriority(): int
    {
        return 1_000;
    }
}

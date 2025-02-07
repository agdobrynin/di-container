<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.tag-a', options: [], priority: 3, priorityMethod: 'getPriority')]
final class TaggedFour implements TaggedInterface
{
    public static function getPriority(): ?int
    {
        return null;
    }

    public static function getTaggedInterfacePriority(): string
    {
        return 'group1:100';
    }

    public static function getOneOfPriority(): int
    {
        return 10_000;
    }
}

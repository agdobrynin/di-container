<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod\Fixtures;

final class TaggedThree
{
    public static function getPriority(): int
    {
        return 3;
    }
}

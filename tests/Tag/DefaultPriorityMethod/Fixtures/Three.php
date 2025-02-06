<?php

declare(strict_types=1);

namespace Tests\Tag\DefaultPriorityMethod\Fixtures;

final class Three
{
    public static function getPriority(): int
    {
        return 3;
    }
}

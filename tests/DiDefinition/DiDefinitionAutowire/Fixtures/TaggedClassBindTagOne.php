<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

class TaggedClassBindTagOne
{
    public static function getTaggedPriority(): int
    {
        return 1000;
    }
}

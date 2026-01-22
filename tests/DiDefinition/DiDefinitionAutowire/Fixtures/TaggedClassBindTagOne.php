<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use ReflectionClass;

class TaggedClassBindTagOne
{
    public static function getTaggedPriority(): ?int
    {
        return 1000;
    }

    public static function getTaggedPriorityReturnArray(): array
    {
        return ['priority' => 100];
    }

    public static function getTaggedPriorityReturnUnionWrong(): array|ReflectionClass|string|null
    {
        return new ReflectionClass(static::class);
    }
}

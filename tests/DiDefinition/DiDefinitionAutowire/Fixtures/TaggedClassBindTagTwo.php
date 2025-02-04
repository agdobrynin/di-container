<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.handlers.one', options: ['validated' => true], priorityMethod: 'tagPriority')]
#[Tag('tags.validator.two', ['login' => 'required|min:5'])]
class TaggedClassBindTagTwo
{
    public static function tagPriority(): int
    {
        return 100;
    }
}

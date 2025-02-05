<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\TaggedDefaultPriorityMethod\Fixtures;

use Kaspi\DiContainer\Attributes\TagDefaultPriorityMethod;

#[TagDefaultPriorityMethod(defaultPriorityMethod: 'getPriority')]
final class TaggedClass
{
    public static function getPriority(): string
    {
        return 'validation:group10:0';
    }
}

<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.one', options: ['key.override' => 'key-service'], priority: 0)]
#[Tag('tags.some-service', options: ['key' => 'some_service.two', 'key.method' => 'self::getKey'], priority: 10)]
final class Two
{
    public static function getKey(): string
    {
        return 'some_service.Dos';
    }
}

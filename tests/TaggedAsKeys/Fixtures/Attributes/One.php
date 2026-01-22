<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.one', options: ['key.override' => 'key-service'], priority: 100)]
#[Tag('tags.some-service', options: ['key' => 'some_service.one', 'key.method' => 'self::getKey'], priority: 1)]
final class One
{
    public static function getKey(string $tag): string
    {
        return match ($tag) {
            'tags.some-service' => 'some_service.Uno',
            default => 'some_service.one-other',
        };
    }
}
